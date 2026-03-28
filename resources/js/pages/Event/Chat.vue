<script setup lang="ts">
import { Head, Link, router, useHttp, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Phone } from 'lucide-vue-next';
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { start as startCall } from '@/routes/connection/call';
import { index as fetchMessages, store as sendMessage } from '@/routes/connection/messages';

type Message = {
    id: number;
    sender_id: number;
    sender_name: string;
    body: string;
    created_at: string;
};

const props = defineProps<{
    event: { id: number; name: string; slug: string };
    connection: { id: number; context?: string };
    peer: { id: number; name: string; company: string };
    icebreaker?: string;
    sharedTags?: string[];
}>();

const page = usePage();
const currentUserId = (page.props as any).auth?.user?.id;

const messages = ref<Message[]>([]);
const messagesContainer = ref<HTMLDivElement>();
const isTyping = ref(false);

const chatRequest = useHttp();
const messageForm = useHttp<{ body: string }>({ body: '' });
const callRequest = useHttp();

async function handleStartCall() {
    try {
        const response = (await callRequest.submit(
            startCall(props.connection.id),
        )) as { call_id: number; room_id: string; expires_at: string };

        router.visit(`/event/${props.event.slug}/connections/${props.connection.id}/call/${response.call_id}`);
    } catch {
        // silently fail
    }
}

async function loadMessages() {
    try {
        const response = (await chatRequest.submit(
            fetchMessages(props.connection.id),
        )) as { data: Message[] };
        messages.value = response.data;
        await nextTick();
        scrollToBottom();
    } catch { /* */ }
}

async function handleSend() {
    if (!messageForm.body.trim()) return;
    try {
        await messageForm.submit(sendMessage(props.connection.id));
        messageForm.reset();
        await loadMessages();
    } catch { /* */ }
}

function scrollToBottom() {
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
}

function formatTime(iso: string): string {
    return new Intl.DateTimeFormat([], { hour: 'numeric', minute: '2-digit' }).format(new Date(iso));
}

const initials = props.peer.name
    .split(' ')
    .map((n) => n[0])
    .join('')
    .toUpperCase();

let echoChannel: unknown = null;

const wasDark = ref(false);

onMounted(() => {
    wasDark.value = document.documentElement.classList.contains('dark');
    document.documentElement.classList.remove('dark');

    loadMessages();
    if (window.Echo) {
        echoChannel = window.Echo.private(`connection.${props.connection.id}.chat`)
            .listen('NewMessage', (e: Message) => {
                messages.value.push(e);
                nextTick(() => scrollToBottom());
            })
            .listenForWhisper('typing', () => {
                isTyping.value = true;
                setTimeout(() => { isTyping.value = false; }, 2000);
            });
    }
});

onUnmounted(() => {
    if (wasDark.value) {
        document.documentElement.classList.add('dark');
    }
    window.Echo?.leave(`connection.${props.connection.id}.chat`);
});

function handleInput() {
    if (echoChannel && typeof (echoChannel as any).whisper === 'function') {
        (echoChannel as any).whisper('typing', {});
    }
}
</script>

<template>
    <div class="flex h-dvh flex-col bg-white">
        <Head :title="`Chat with ${peer.name}`" />

        <!-- Header -->
        <div class="flex items-center gap-3 border-b border-neutral-100 px-4 py-3 safe-area-pt">
            <Link
                :href="`/event/${event.slug}/connections`"
                class="p-1 text-neutral-400 transition hover:text-neutral-600"
            >
                <ArrowLeft class="size-5" />
            </Link>

            <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700">
                {{ initials }}
            </div>

            <div class="min-w-0 flex-1">
                <h1 class="truncate text-sm font-semibold text-neutral-900">{{ peer.name }}</h1>
                <p class="truncate text-xs text-neutral-500">
                    {{ connection.context || peer.company }}{{ sharedTags?.length ? ` · ${sharedTags[0]}` : '' }}
                </p>
            </div>

            <button
                class="p-2 text-neutral-400 transition hover:text-neutral-600"
                :disabled="callRequest.processing"
                @click="handleStartCall"
            >
                <Phone class="size-5" />
            </button>
        </div>

        <!-- Icebreaker banner -->
        <div
            v-if="icebreaker && messages.length === 0"
            class="border-b border-indigo-100 bg-indigo-50 px-4 py-2 text-sm text-indigo-700"
        >
            💡 Try asking: "{{ icebreaker }}"
        </div>

        <!-- Messages -->
        <div
            ref="messagesContainer"
            class="flex-1 space-y-3 overflow-y-auto p-4"
        >
            <div v-if="chatRequest.processing" class="flex items-center justify-center py-8">
                <div class="size-5 animate-spin rounded-full border-2 border-indigo-600 border-t-transparent" />
            </div>

            <div
                v-if="!chatRequest.processing && messages.length === 0"
                class="py-12 text-center text-sm text-neutral-400"
            >
                Noch keine Nachrichten. Sagen Sie Hallo!
            </div>

            <div
                v-for="msg in messages"
                :key="msg.id"
                class="flex"
                :class="msg.sender_id === currentUserId ? 'justify-end' : 'justify-start'"
            >
                <!-- Other person's avatar -->
                <div
                    v-if="msg.sender_id !== currentUserId"
                    class="mr-2 mt-auto flex size-7 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[10px] font-semibold text-indigo-700"
                >
                    {{ initials }}
                </div>

                <div
                    class="max-w-[75%] rounded-2xl px-4 py-2.5"
                    :class="msg.sender_id === currentUserId
                        ? 'rounded-br-md bg-indigo-600 text-white'
                        : 'rounded-bl-md bg-neutral-100 text-neutral-900'"
                >
                    <p class="text-sm">{{ msg.body }}</p>
                    <p
                        class="mt-0.5 text-[10px]"
                        :class="msg.sender_id === currentUserId ? 'text-indigo-200' : 'text-neutral-400'"
                    >
                        {{ formatTime(msg.created_at) }}
                    </p>
                </div>
            </div>

            <!-- Typing indicator -->
            <div v-if="isTyping" class="flex items-center gap-2">
                <div class="flex size-7 items-center justify-center rounded-full bg-indigo-100 text-[10px] font-semibold text-indigo-700">
                    {{ initials }}
                </div>
                <div class="flex gap-1 rounded-2xl bg-neutral-100 px-4 py-3">
                    <span class="size-1.5 animate-bounce rounded-full bg-neutral-400 [animation-delay:0ms]" />
                    <span class="size-1.5 animate-bounce rounded-full bg-neutral-400 [animation-delay:150ms]" />
                    <span class="size-1.5 animate-bounce rounded-full bg-neutral-400 [animation-delay:300ms]" />
                </div>
            </div>
        </div>

        <!-- Input bar -->
        <div class="border-t border-neutral-100 p-3 safe-area-pb">
            <div class="flex items-center gap-2">
                <input
                    v-model="messageForm.body"
                    type="text"
                    maxlength="500"
                    placeholder="Nachricht eingeben..."
                    class="flex-1 rounded-full border border-neutral-200 bg-neutral-50 px-4 py-2.5 text-sm outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"
                    @keydown.enter="handleSend"
                    @input="handleInput"
                />
                <button
                    class="flex size-10 items-center justify-center rounded-full bg-indigo-600 text-white transition hover:bg-indigo-700 disabled:opacity-40"
                    :disabled="messageForm.processing || !messageForm.body?.trim()"
                    @click="handleSend"
                >
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13" />
                        <polygon points="22 2 15 22 11 13 2 9 22 2" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.safe-area-pt {
    padding-top: env(safe-area-inset-top);
}
.safe-area-pb {
    padding-bottom: env(safe-area-inset-bottom);
}
</style>
