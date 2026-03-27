<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useHaptics } from '@/composables/useHaptics';
import { ping } from '@/routes/event';

const props = defineProps<{
    participant: {
        id: number;
        name: string;
        company?: string;
        role_title?: string;
        intent?: string;
        participant_type: string;
        status: string;
        context_badge: string | null;
        icebreaker_answer?: string;
        open_to_call: boolean;
        interest_tags: string[];
    } | null;
    eventSlug: string;
    open: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    pinged: [userId: number];
}>();

const { ping: hapticPing } = useHaptics();
const pingRequest = useHttp();

const statusLabels: Record<string, string> = {
    available: 'Available',
    in_session: 'In Session',
    at_booth: 'At Booth',
    busy: 'Busy',
    away: 'Away',
};

const statusColors: Record<string, string> = {
    available: 'bg-green-500',
    in_session: 'bg-blue-500',
    at_booth: 'bg-purple-500',
    busy: 'bg-amber-500',
    away: 'bg-neutral-400',
};

async function handlePing() {
    if (!props.participant) return;
    hapticPing();
    try {
        await pingRequest.submit(
            ping({ event: props.eventSlug, user: props.participant.id }),
        );
        emit('pinged', props.participant.id);
    } catch {
        // silently fail
    }
}

const initials = (name: string) =>
    name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase();
</script>

<template>
    <Sheet :open="open" @update:open="emit('update:open', $event)">
        <SheetContent side="bottom" class="rounded-t-3xl px-6 pb-8 pt-4">
            <template v-if="participant">
                <!-- Drag handle -->
                <div class="mx-auto mb-4 h-1 w-10 rounded-full bg-neutral-200" />

                <SheetHeader class="space-y-4 text-left">
                    <div class="flex items-start gap-4">
                        <div class="flex size-14 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-lg font-bold text-indigo-700">
                            {{ initials(participant.name) }}
                        </div>

                        <div class="min-w-0 flex-1">
                            <SheetTitle class="text-lg font-bold text-neutral-900">
                                {{ participant.name }}
                            </SheetTitle>
                            <SheetDescription class="text-sm text-neutral-500">
                                {{ participant.role_title }}{{ participant.company ? ` at ${participant.company}` : '' }}
                            </SheetDescription>
                            <div class="mt-1 flex items-center gap-2 text-xs text-neutral-500">
                                <span
                                    class="inline-block size-2 rounded-full"
                                    :class="statusColors[participant.status] ?? 'bg-neutral-400'"
                                />
                                {{ statusLabels[participant.status] ?? participant.status }}
                                · {{ participant.participant_type === 'physical' ? '📍 Physical' : '🌐 Remote' }}
                            </div>
                        </div>
                    </div>
                </SheetHeader>

                <!-- Interest tags -->
                <div class="mt-4 flex flex-wrap gap-2">
                    <span
                        v-for="tag in participant.interest_tags"
                        :key="tag"
                        class="rounded-full border border-neutral-200 px-3 py-1 text-xs font-medium text-neutral-700"
                    >
                        {{ tag }}
                    </span>
                </div>

                <!-- Icebreaker -->
                <div
                    v-if="participant.icebreaker_answer"
                    class="mt-4 rounded-xl bg-neutral-50 p-4"
                >
                    <p class="text-[11px] font-semibold tracking-wider text-neutral-400 uppercase">
                        Icebreaker
                    </p>
                    <p class="mt-1 text-sm text-neutral-700">
                        "{{ participant.icebreaker_answer }}"
                    </p>
                </div>

                <!-- Intent badges -->
                <div class="mt-4 flex flex-wrap gap-2">
                    <span
                        v-if="participant.open_to_call"
                        class="rounded-full border border-green-200 bg-green-50 px-3 py-1 text-xs font-medium text-green-700"
                    >
                        Open to call
                    </span>
                    <span
                        v-if="participant.intent"
                        class="rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700"
                    >
                        {{ participant.intent }}
                    </span>
                </div>

                <!-- Ping button -->
                <button
                    class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 py-3.5 text-base font-semibold text-white transition hover:bg-indigo-700"
                    :disabled="pingRequest.processing"
                    @click="handlePing"
                >
                    👋 Ping
                </button>

                <!-- Block / Report -->
                <div class="mt-3 flex items-center justify-center gap-6 text-xs text-neutral-400">
                    <button class="transition hover:text-neutral-600">Block</button>
                    <button class="transition hover:text-neutral-600">Report</button>
                </div>
            </template>
        </SheetContent>
    </Sheet>
</template>
