<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    open: boolean;
    match: {
        connectionId: number;
        user: {
            name: string;
            role_title?: string;
            company?: string;
        };
        sharedTags: string[];
        icebreaker?: string;
    } | null;
    currentUserName: string;
    eventSlug: string;
}>();

const emit = defineEmits<{
    close: [];
}>();

const initials = (name: string) =>
    name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase();
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open && match"
                class="fixed inset-0 z-[100] flex flex-col items-center justify-center bg-indigo-600 px-6"
            >
                <!-- Overlapping avatars -->
                <div class="flex items-center -space-x-4">
                    <div class="flex size-20 items-center justify-center rounded-full border-4 border-indigo-600 bg-indigo-200 text-xl font-bold text-indigo-800">
                        {{ initials(currentUserName) }}
                    </div>
                    <div class="flex size-20 items-center justify-center rounded-full border-4 border-indigo-600 bg-rose-200 text-xl font-bold text-rose-800">
                        {{ initials(match.user.name) }}
                    </div>
                </div>

                <h1 class="mt-6 text-3xl font-bold text-white">It's a match!</h1>
                <p class="mt-2 text-center text-sm text-indigo-100">
                    You and {{ match.user.name }} both pinged each other
                </p>

                <!-- Shared tags -->
                <div class="mt-4 flex flex-wrap justify-center gap-2">
                    <span
                        v-for="tag in match.sharedTags"
                        :key="tag"
                        class="rounded-full bg-indigo-500 px-3 py-1 text-xs font-medium text-white"
                    >
                        {{ tag }}
                    </span>
                </div>

                <!-- Icebreaker card -->
                <div
                    v-if="match.icebreaker"
                    class="mt-6 w-full max-w-sm rounded-2xl bg-indigo-500/50 p-4"
                >
                    <p class="text-[11px] font-semibold tracking-wider text-indigo-200 uppercase">
                        Start with
                    </p>
                    <p class="mt-1 text-sm text-white">
                        "{{ match.icebreaker }}"
                    </p>
                </div>

                <!-- Actions -->
                <div class="mt-8 w-full max-w-sm space-y-3">
                    <Link
                        :href="`/event/${eventSlug}/connections/${match.connectionId}/chat`"
                        class="flex w-full items-center justify-center rounded-xl border-2 border-white bg-transparent py-3.5 text-base font-semibold text-white transition hover:bg-white/10"
                    >
                        Start Chat
                    </Link>

                    <Link
                        :href="`/event/${eventSlug}/connections/${match.connectionId}/chat`"
                        class="flex w-full items-center justify-center rounded-xl bg-white py-3.5 text-base font-semibold text-indigo-600 transition hover:bg-indigo-50"
                    >
                        Video Call
                    </Link>

                    <button
                        class="w-full py-2 text-center text-sm text-indigo-200 transition hover:text-white"
                        @click="emit('close')"
                    >
                        Save for Later
                    </button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
