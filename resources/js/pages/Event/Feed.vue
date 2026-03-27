<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Activity, Sparkles, Users } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';
import ParticipantCard from '@/components/presence/ParticipantCard.vue';
import PresenceFilters from '@/components/presence/PresenceFilters.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import Heading from '@/components/Heading.vue';

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
    filters: { type?: string; status?: string; tag?: string };
}>();

const liveParticipants = ref([...props.participants]);

function handlePing(userId: number) {
    console.log('Ping user:', userId);
}

const lastOccurredAt = ref<Record<number, string>>({});

onMounted(() => {
    if (!window.Echo) {
        return;
    }

    const channel = window.Echo.join(`event.${props.event.id}.presence`);

    channel
        .here(() => {
            // Initial presence list from Reverb
        })
        .joining((user: { id: number; name: string }) => {
            if (!liveParticipants.value.find((p) => p.id === user.id)) {
                router.reload({ only: ['participants'] });
            }
        })
        .leaving(() => {
            // Don't remove — status will go to 'away' via server
        });

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
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
        <Head :title="`${event.name} Feed`" />

        <Card
            class="overflow-hidden border-border/70 bg-gradient-to-br from-primary/8 via-card to-card py-0 shadow-sm"
        >
            <CardContent class="space-y-5 p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        title="Presence Feed"
                        :description="`${liveParticipants.length} participants currently visible in ${event.name}.`"
                    />

                    <Badge
                        class="rounded-full px-3 py-1 text-[11px] font-semibold tracking-[0.16em] uppercase"
                    >
                        Live
                    </Badge>
                </div>

                <div class="grid gap-3 md:grid-cols-3">
                    <div
                        class="rounded-2xl border border-border/70 bg-background/80 p-4"
                    >
                        <div
                            class="flex items-center gap-2 text-sm text-muted-foreground"
                        >
                            <Users class="size-4" />
                            Total visible
                        </div>
                        <p class="mt-3 text-2xl font-semibold">
                            {{ liveParticipants.length }}
                        </p>
                    </div>

                    <div
                        class="rounded-2xl border border-border/70 bg-background/80 p-4"
                    >
                        <div
                            class="flex items-center gap-2 text-sm text-muted-foreground"
                        >
                            <Activity class="size-4" />
                            Open to connect
                        </div>
                        <p class="mt-3 text-2xl font-semibold">
                            {{
                                liveParticipants.filter(
                                    (participant) => participant.open_to_call,
                                ).length
                            }}
                        </p>
                    </div>

                    <div
                        class="rounded-2xl border border-border/70 bg-background/80 p-4"
                    >
                        <div
                            class="flex items-center gap-2 text-sm text-muted-foreground"
                        >
                            <Sparkles class="size-4" />
                            Filter state
                        </div>
                        <p class="mt-3 text-sm font-medium text-foreground">
                            {{ filters.type || 'All types' }} /
                            {{ filters.status || 'All statuses' }}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <PresenceFilters :filters="filters" :event-slug="event.slug" />

        <div class="space-y-3">
            <ParticipantCard
                v-for="participant in liveParticipants"
                :key="participant.id"
                :participant="participant"
                @ping="handlePing"
            />

            <Card
                v-if="liveParticipants.length === 0"
                class="border-dashed border-border/70 bg-card/80 py-0 shadow-sm"
            >
                <CardContent class="py-12 text-center">
                    <p class="font-medium">
                        No participants match your filters.
                    </p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Broaden the filters to bring more of the event graph
                        back into view.
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
