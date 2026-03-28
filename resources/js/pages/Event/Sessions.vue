<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { show } from '@/routes/event/sessions';

const remindedSessions = ref<Set<number>>(new Set());

function remindSession(sessionId: number, event: MouseEvent) {
    event.preventDefault();
    event.stopPropagation();
    remindedSessions.value.add(sessionId);
}

const props = defineProps<{
    event: { id: number; name: string; slug: string };
    sessions: Array<{
        id: number;
        title: string;
        description: string;
        speaker: string;
        room: string;
        starts_at: string;
        ends_at: string;
        is_live: boolean;
        qa_enabled: boolean;
        attendee_count: number;
        physical_count: number;
        remote_count: number;
        is_checked_in: boolean;
    }>;
}>();

function formatTimeRange(startsAt: string, endsAt: string): string {
    const formatter = new Intl.DateTimeFormat([], {
        hour: 'numeric',
        minute: '2-digit',
    });
    return `${formatter.format(new Date(startsAt))} – ${formatter.format(new Date(endsAt))}`;
}

const today = new Date().toLocaleDateString([], { weekday: 'long', month: 'long', day: 'numeric' });
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Head :title="`${event.name} Sessions`" />

        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Sessions</h1>
            <p class="text-sm text-neutral-500">{{ today }}</p>
        </div>

        <div class="space-y-3">
            <Link
                v-for="session in sessions"
                :key="session.id"
                :href="show({ event: props.event.slug, session: session.id }).url"
                class="block rounded-2xl border bg-white p-4 transition"
                :class="session.is_live ? 'border-red-200 shadow-sm' : 'border-neutral-100'"
            >
                <!-- Live badge + time -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span
                            v-if="session.is_live"
                            class="inline-flex items-center gap-1 rounded-full bg-red-500 px-2 py-0.5 text-[11px] font-semibold text-white"
                        >
                            <span class="size-1.5 animate-pulse rounded-full bg-white" />
                            LIVE
                        </span>
                        <span v-else class="text-xs text-neutral-400">
                            {{ formatTimeRange(session.starts_at, session.ends_at) }}
                        </span>
                    </div>
                    <span v-if="session.is_live" class="text-xs text-neutral-400">
                        {{ formatTimeRange(session.starts_at, session.ends_at) }}
                    </span>
                </div>

                <!-- Title + speaker -->
                <h2 class="mt-2 text-base font-semibold text-neutral-900">{{ session.title }}</h2>
                <p class="mt-0.5 text-sm text-neutral-500">
                    {{ session.speaker || 'Referent folgt' }} · {{ session.room || 'Raum folgt' }}
                </p>

                <!-- Bottom row: counts + action -->
                <div class="mt-3 flex items-center justify-between">
                    <div class="flex items-center gap-3 text-xs text-neutral-500">
                        <span class="flex items-center gap-1">
                            📍 {{ session.physical_count }}
                        </span>
                        <span class="flex items-center gap-1">
                            🌐 {{ session.remote_count }}
                        </span>
                    </div>

                    <span
                        v-if="session.is_checked_in"
                        class="rounded-full bg-green-500 px-3 py-1 text-xs font-semibold text-white"
                    >
                        ✓ Eingecheckt
                    </span>
                    <button
                        v-else-if="!session.is_live && !remindedSessions.has(session.id)"
                        class="rounded-full border border-neutral-200 px-3 py-1 text-xs font-medium text-neutral-600 transition hover:bg-neutral-50"
                        @click="remindSession(session.id, $event)"
                    >
                        Erinnern
                    </button>
                    <span
                        v-else-if="!session.is_live && remindedSessions.has(session.id)"
                        class="rounded-full border border-green-200 bg-green-50 px-3 py-1 text-xs font-medium text-green-600"
                    >
                        ✓ Erinnerung gesetzt
                    </span>
                </div>
            </Link>

            <p
                v-if="sessions.length === 0"
                class="py-12 text-center text-sm text-neutral-400"
            >
                Noch keine Sessions geplant.
            </p>
        </div>
    </div>
</template>
