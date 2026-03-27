<script setup lang="ts">
import { Head, Link, router, useHttp } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref, watch } from 'vue';
import ParticipantRow from '@/components/presence/ParticipantRow.vue';
import PresenceFilters from '@/components/presence/PresenceFilters.vue';
import { useHaptics } from '@/composables/useHaptics';
import { ping, search } from '@/routes/event';

const { ping: hapticPing } = useHaptics();

const props = defineProps<{
    event: { id: number; name: string; slug: string };
    participants: Array<{
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
    }>;
    suggestion?: {
        id: number;
        name: string;
        company?: string;
        role_title?: string;
        participant_type: string;
        status: string;
        interest_tags: string[];
        shared_tags: string[];
        shared_context?: string;
        time_left?: string;
    } | null;
    filters: { type?: string; status?: string; tag?: string };
    availableTags?: string[];
}>();

const liveParticipants = ref([...props.participants]);

watch(
    () => props.participants,
    (updated) => {
        liveParticipants.value = [...updated];
    },
);

const pingRequest = useHttp();

async function handlePing(userId: number) {
    hapticPing();
    try {
        await pingRequest.submit(
            ping({ event: props.event.slug, user: userId }),
        );
    } catch {
        // silently fail
    }
}

const lastOccurredAt = ref<Record<number, string>>({});

onMounted(() => {
    if (!window.Echo) {
        return;
    }

    const channel = window.Echo.join(`event.${props.event.id}.presence`);

    channel
        .here(() => {})
        .joining((user: { id: number; name: string }) => {
            if (!liveParticipants.value.find((p) => p.id === user.id)) {
                router.reload({ only: ['participants'] });
            }
        })
        .leaving(() => {});

    channel.listen(
        'PresenceStateChanged',
        (data: {
            user_id: number;
            status: string;
            context_badge: string | null;
            participant_type: string;
            occurred_at: string;
        }) => {
            const last = lastOccurredAt.value[data.user_id];

            if (last && data.occurred_at < last) {
                return;
            }

            lastOccurredAt.value[data.user_id] = data.occurred_at;

            const participant = liveParticipants.value.find(
                (p) => p.id === data.user_id,
            );

            if (participant) {
                participant.status = data.status;
                participant.context_badge = data.context_badge;
            }
        },
    );
});

onUnmounted(() => {
    window.Echo?.leave(`event.${props.event.id}.presence`);
});
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Head :title="`${event.name} Feed`" />

        <!-- Search bar -->
        <Link
            :href="search(event.slug).url"
            class="flex items-center gap-2 rounded-full border border-neutral-200 bg-neutral-50 px-4 py-2.5 text-sm text-neutral-400 transition hover:border-neutral-300"
        >
            <Search class="size-4" />
            Search people, tags...
        </Link>

        <!-- Filter pills -->
        <PresenceFilters
            :filters="filters"
            :event-slug="event.slug"
            :available-tags="availableTags"
        />

        <!-- RIGHT NOW — Featured suggestion -->
        <div v-if="suggestion" class="space-y-2">
            <div class="flex items-center gap-1.5 text-xs font-semibold tracking-wider text-indigo-500 uppercase">
                <span>✨</span> Right now
            </div>

            <div class="rounded-2xl border border-indigo-100 bg-white p-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="relative shrink-0">
                        <div class="flex size-12 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700">
                            {{ suggestion.name.split(' ').map(n => n[0]).join('') }}
                        </div>
                        <span class="absolute -bottom-0.5 -left-0.5 size-3 rounded-full border-2 border-white bg-green-500" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-base font-semibold text-neutral-900">{{ suggestion.name }}</span>
                                <p class="text-sm text-neutral-500">
                                    {{ suggestion.role_title }}{{ suggestion.company ? `, ${suggestion.company}` : '' }}
                                    · {{ suggestion.participant_type === 'physical' ? '📍' : '🌐' }} {{ suggestion.participant_type === 'physical' ? 'Physical' : 'Remote' }}
                                </p>
                            </div>
                            <span v-if="suggestion.time_left" class="text-xs text-neutral-400">{{ suggestion.time_left }}</span>
                        </div>

                        <p class="mt-1 text-sm text-indigo-600">
                            {{ suggestion.shared_context || `Both interested in ${suggestion.shared_tags.join(' · ')}` }}
                        </p>

                        <div class="mt-3 flex items-center gap-2">
                            <button
                                class="flex flex-1 items-center justify-center gap-1.5 rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700"
                                @click="handlePing(suggestion.id)"
                            >
                                👋 Ping
                            </button>
                            <button
                                class="rounded-full border border-neutral-200 bg-white px-4 py-2 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50"
                            >
                                Later
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- People list -->
        <div>
            <p class="mb-2 text-xs font-semibold tracking-wider text-neutral-400 uppercase">
                People · {{ liveParticipants.length }} here
            </p>

            <div class="rounded-2xl bg-white">
                <ParticipantRow
                    v-for="participant in liveParticipants"
                    :key="participant.id"
                    :participant="participant"
                    @ping="handlePing"
                />

                <p
                    v-if="liveParticipants.length === 0"
                    class="py-12 text-center text-sm text-neutral-400"
                >
                    No participants match your filters.
                </p>
            </div>
        </div>
    </div>
</template>
