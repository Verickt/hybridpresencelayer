<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, Mic2, Users } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import Heading from '@/components/Heading.vue';
import { show } from '@/routes/event/sessions';

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
    }>;
}>();

function formatTimeRange(startsAt: string, endsAt: string): string {
    const formatter = new Intl.DateTimeFormat([], {
        hour: 'numeric',
        minute: '2-digit',
    });

    return `${formatter.format(new Date(startsAt))} - ${formatter.format(new Date(endsAt))}`;
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
        <Head :title="`${event.name} Sessions`" />

        <Card
            class="border-border/70 bg-gradient-to-br from-primary/8 via-card to-card py-0 shadow-sm"
        >
            <CardContent class="space-y-3 p-6">
                <Heading
                    title="Sessions"
                    :description="`${sessions.length} sessions scheduled for ${event.name}.`"
                />
                <p class="text-sm text-muted-foreground">
                    A shared schedule for in-room and remote participants,
                    styled with the same primitives as the rest of the app.
                </p>
            </CardContent>
        </Card>

        <div class="space-y-3">
            <Card
                v-for="session in sessions"
                :key="session.id"
                class="border-border/70 py-0 shadow-sm"
            >
                <CardContent class="space-y-4 p-6">
                    <div
                        class="flex flex-wrap items-start justify-between gap-3"
                    >
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-lg font-semibold">
                                    {{ session.title }}
                                </h2>
                                <Badge
                                    v-if="session.is_live"
                                    class="rounded-full px-2.5 py-1 text-[11px] font-semibold tracking-[0.12em] uppercase"
                                >
                                    Live
                                </Badge>
                                <Badge
                                    v-if="session.qa_enabled"
                                    variant="secondary"
                                    class="rounded-full px-2.5 py-1 text-[11px]"
                                >
                                    Q&amp;A
                                </Badge>
                            </div>

                            <p
                                v-if="session.description"
                                class="text-sm text-muted-foreground"
                            >
                                {{ session.description }}
                            </p>
                        </div>

                        <Button as-child variant="outline" class="rounded-full">
                            <Link
                                :href="
                                    show({
                                        event: props.event.slug,
                                        session: session.id,
                                    })
                                "
                            >
                                View
                            </Link>
                        </Button>
                    </div>

                    <div
                        class="grid gap-3 text-sm text-muted-foreground md:grid-cols-3"
                    >
                        <div class="flex items-center gap-2">
                            <CalendarDays class="size-4" />
                            {{
                                formatTimeRange(
                                    session.starts_at,
                                    session.ends_at,
                                )
                            }}
                        </div>
                        <div class="flex items-center gap-2">
                            <Mic2 class="size-4" />
                            {{ session.speaker || 'Speaker TBA' }}
                        </div>
                        <div class="flex items-center gap-2">
                            <Users class="size-4" />
                            {{ session.attendee_count }} attendee{{
                                session.attendee_count !== 1 ? 's' : ''
                            }}
                        </div>
                    </div>

                    <p class="text-sm font-medium text-foreground">
                        {{ session.room || 'Room to be announced' }}
                    </p>
                </CardContent>
            </Card>

            <Card
                v-if="sessions.length === 0"
                class="border-dashed border-border/70 py-0 shadow-sm"
            >
                <CardContent class="py-12 text-center">
                    <p class="font-medium">No sessions are scheduled yet.</p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Session cards will appear here once the event agenda is
                        published.
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
