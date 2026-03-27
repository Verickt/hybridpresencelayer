<script setup lang="ts">
import { Head, useHttp } from '@inertiajs/vue3';
import { Camera, CameraOff, Mic, MicOff, PhoneOff, Timer } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { extend as extendCall, end as endCall } from '@/routes/connection/call';

const props = defineProps<{
    event: { id: number; name: string; slug: string };
    connection: { id: number };
    call: { id: number; room_id: string; expires_at: string; extensions: number };
    peer: { id: number; name: string };
}>();

const localVideo = ref<HTMLVideoElement>();
const remoteVideo = ref<HTMLVideoElement>();

const localStream = ref<MediaStream | null>(null);
const isCameraOn = ref(true);
const isMuted = ref(false);
const callEnded = ref(false);
const webrtcSupported = ref(true);
const extensions = ref(props.call.extensions);
const expiresAt = ref(new Date(props.call.expires_at));
const remainingSeconds = ref(0);
const timeUp = ref(false);

const maxExtensions = 2;

const canExtend = computed(() => extensions.value < maxExtensions);

const callHttp = useHttp();

const formattedTime = computed(() => {
    const mins = Math.floor(remainingSeconds.value / 60);
    const secs = remainingSeconds.value % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
});

const isLowTime = computed(() => remainingSeconds.value <= 30 && remainingSeconds.value > 0);

let timerInterval: ReturnType<typeof setInterval> | null = null;

function updateTimer() {
    const now = Date.now();
    const diff = Math.max(0, Math.floor((expiresAt.value.getTime() - now) / 1000));
    remainingSeconds.value = diff;

    if (diff <= 0 && !timeUp.value) {
        timeUp.value = true;
    }
}

async function startMedia() {
    if (!navigator.mediaDevices?.getUserMedia) {
        webrtcSupported.value = false;
        return;
    }

    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: true,
            audio: true,
        });
        localStream.value = stream;

        if (localVideo.value) {
            localVideo.value.srcObject = stream;
        }
    } catch {
        webrtcSupported.value = false;
    }
}

function toggleCamera() {
    if (!localStream.value) {
        return;
    }
    const videoTrack = localStream.value.getVideoTracks()[0];
    if (videoTrack) {
        videoTrack.enabled = !videoTrack.enabled;
        isCameraOn.value = videoTrack.enabled;
    }
}

function toggleMute() {
    if (!localStream.value) {
        return;
    }
    const audioTrack = localStream.value.getAudioTracks()[0];
    if (audioTrack) {
        audioTrack.enabled = !audioTrack.enabled;
        isMuted.value = !audioTrack.enabled;
    }
}

async function handleExtend() {
    if (!canExtend.value || callHttp.processing) {
        return;
    }

    try {
        const response = (await callHttp.submit(
            extendCall({ connection: props.connection.id, call: props.call.id }),
        )) as { expires_at: string; extensions: number };

        expiresAt.value = new Date(response.expires_at);
        extensions.value = response.extensions;
        timeUp.value = false;
    } catch {
        // silently fail
    }
}

async function handleEnd() {
    try {
        await callHttp.submit(
            endCall({ connection: props.connection.id, call: props.call.id }),
        );
    } catch {
        // silently fail
    }

    cleanup();
    callEnded.value = true;
}

function cleanup() {
    if (localStream.value) {
        localStream.value.getTracks().forEach((track) => track.stop());
        localStream.value = null;
    }
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
}

onMounted(() => {
    startMedia();
    updateTimer();
    timerInterval = setInterval(updateTimer, 1000);
});

onUnmounted(() => {
    cleanup();
});
</script>

<template>
    <div class="flex h-dvh flex-col bg-black text-white">
        <Head :title="`Call with ${peer.name}`" />

        <!-- WebRTC not supported fallback -->
        <div v-if="!webrtcSupported" class="flex flex-1 flex-col items-center justify-center gap-4 p-6 text-center">
            <div class="rounded-2xl bg-white/10 p-6">
                <CameraOff class="mx-auto size-12 text-white/60" />
            </div>
            <h2 class="text-lg font-semibold">Video calls not supported</h2>
            <p class="max-w-sm text-sm text-white/60">
                Your browser does not support video calls or camera access was denied. Please try a different browser or check your permissions.
            </p>
            <a
                :href="`/event/${event.slug}/connections`"
                class="mt-2 rounded-xl bg-white/10 px-4 py-2 text-sm transition-colors hover:bg-white/20"
            >
                Back to connections
            </a>
        </div>

        <!-- Call ended -->
        <div v-else-if="callEnded" class="flex flex-1 flex-col items-center justify-center gap-4 p-6 text-center">
            <h2 class="text-lg font-semibold">Call ended</h2>
            <p class="text-sm text-white/60">Your call with {{ peer.name }} has ended.</p>
            <a
                :href="`/event/${event.slug}/connections`"
                class="mt-2 rounded-xl bg-white/10 px-4 py-2 text-sm transition-colors hover:bg-white/20"
            >
                Back to connections
            </a>
        </div>

        <!-- Active call -->
        <template v-else>
            <!-- Video area -->
            <div class="relative flex-1">
                <!-- Remote video (full) -->
                <div class="flex h-full items-center justify-center bg-black">
                    <video
                        ref="remoteVideo"
                        autoplay
                        playsinline
                        class="h-full w-full object-cover"
                    />
                    <div
                        v-if="!remoteVideo?.srcObject"
                        class="absolute inset-0 flex flex-col items-center justify-center gap-2"
                    >
                        <div class="flex size-20 items-center justify-center rounded-full bg-white/10 text-2xl font-semibold">
                            {{ peer.name.charAt(0) }}
                        </div>
                        <p class="text-sm text-white/60">Waiting for {{ peer.name }}...</p>
                    </div>
                </div>

                <!-- Local video (PiP) -->
                <div class="absolute right-4 top-4 h-32 w-24 overflow-hidden rounded-xl border-2 border-white/20 bg-black shadow-lg">
                    <video
                        ref="localVideo"
                        autoplay
                        playsinline
                        muted
                        class="h-full w-full object-cover"
                        :class="{ 'opacity-0': !isCameraOn }"
                    />
                    <div
                        v-if="!isCameraOn"
                        class="absolute inset-0 flex items-center justify-center bg-zinc-800"
                    >
                        <CameraOff class="size-5 text-white/40" />
                    </div>
                </div>

                <!-- Timer badge -->
                <div
                    class="absolute left-4 top-4 flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium backdrop-blur-sm"
                    :class="[
                        timeUp
                            ? 'bg-red-500/80 text-white'
                            : isLowTime
                                ? 'bg-amber-500/80 text-white'
                                : 'bg-black/50 text-white/80',
                    ]"
                >
                    <Timer class="size-3.5" />
                    <span v-if="timeUp">Time's up!</span>
                    <span v-else>{{ formattedTime }}</span>
                </div>

                <!-- Time's up overlay -->
                <div
                    v-if="timeUp"
                    class="absolute inset-x-0 bottom-0 flex items-center justify-center gap-3 bg-gradient-to-t from-black/80 to-transparent px-4 pb-6 pt-16"
                >
                    <Button
                        variant="destructive"
                        class="rounded-xl"
                        @click="handleEnd"
                    >
                        <PhoneOff class="mr-1.5 size-4" />
                        End Call
                    </Button>
                    <Button
                        v-if="canExtend"
                        class="rounded-xl bg-white text-black hover:bg-white/90"
                        :disabled="callHttp.processing"
                        @click="handleExtend"
                    >
                        Extend +3 Min
                    </Button>
                </div>
            </div>

            <!-- Controls bar -->
            <div class="flex items-center justify-center gap-4 bg-zinc-900 px-4 py-4">
                <button
                    class="flex size-12 items-center justify-center rounded-full transition-colors"
                    :class="isMuted ? 'bg-red-500/80 text-white' : 'bg-white/10 text-white hover:bg-white/20'"
                    @click="toggleMute"
                >
                    <MicOff v-if="isMuted" class="size-5" />
                    <Mic v-else class="size-5" />
                </button>

                <button
                    class="flex size-12 items-center justify-center rounded-full transition-colors"
                    :class="!isCameraOn ? 'bg-red-500/80 text-white' : 'bg-white/10 text-white hover:bg-white/20'"
                    @click="toggleCamera"
                >
                    <CameraOff v-if="!isCameraOn" class="size-5" />
                    <Camera v-else class="size-5" />
                </button>

                <button
                    class="flex size-12 items-center justify-center rounded-full bg-red-500 text-white transition-colors hover:bg-red-600"
                    @click="handleEnd"
                >
                    <PhoneOff class="size-5" />
                </button>
            </div>

            <!-- Extension info -->
            <div
                v-if="extensions > 0"
                class="bg-zinc-900 pb-2 text-center text-xs text-white/40"
            >
                {{ extensions }}/{{ maxExtensions }} extensions used
            </div>
        </template>
    </div>
</template>
