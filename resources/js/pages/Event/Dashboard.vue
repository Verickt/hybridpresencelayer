<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Activity, Handshake, LayoutGrid, Users } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import Heading from '@/components/Heading.vue';

defineProps<{
    event: { id: number; name: string; slug: string };
    overview: Record<string, number>;
    sessionAnalytics: Array<Record<string, unknown>>;
    boothPerformance: Array<Record<string, unknown>>;
}>();
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
        <Head :title="`${event.name} Dashboard`" />

        <Card
            class="border-border/70 bg-gradient-to-br from-primary/8 via-card to-card py-0 shadow-sm"
        >
            <CardContent class="space-y-3 p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        title="Organizer overview"
                        :description="`Live event metrics for ${event.name}.`"
                    />
                    <Badge variant="secondary" class="rounded-full px-3 py-1">
                        {{ event.slug }}
                    </Badge>
                </div>
            </CardContent>
        </Card>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <Card class="border-border/70 py-0 shadow-sm">
                <CardContent class="space-y-3 p-6">
                    <div
                        class="flex items-center gap-2 text-sm text-muted-foreground"
                    >
                        <Users class="size-4" />
                        Total active
                    </div>
                    <p class="text-3xl font-semibold tracking-tight">
                        {{ overview.total_active }}
                    </p>
                </CardContent>
            </Card>

            <Card class="border-border/70 py-0 shadow-sm">
                <CardContent class="space-y-3 p-6">
                    <div
                        class="flex items-center gap-2 text-sm text-muted-foreground"
                    >
                        <Handshake class="size-4" />
                        Connections
                    </div>
                    <p class="text-3xl font-semibold tracking-tight">
                        {{ overview.total_connections }}
                    </p>
                </CardContent>
            </Card>

            <Card class="border-border/70 py-0 shadow-sm">
                <CardContent class="space-y-3 p-6">
                    <div
                        class="flex items-center gap-2 text-sm text-muted-foreground"
                    >
                        <Activity class="size-4" />
                        Interaction rate
                    </div>
                    <p class="text-3xl font-semibold tracking-tight">
                        {{ overview.interaction_rate }}%
                    </p>
                </CardContent>
            </Card>

            <Card class="border-border/70 py-0 shadow-sm">
                <CardContent class="space-y-3 p-6">
                    <div
                        class="flex items-center gap-2 text-sm text-muted-foreground"
                    >
                        <LayoutGrid class="size-4" />
                        Cross-pollination
                    </div>
                    <p class="text-3xl font-semibold tracking-tight">
                        {{ overview.cross_pollination_rate }}%
                    </p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <Card class="border-border/70 py-0 shadow-sm">
                <CardContent class="space-y-4 p-6">
                    <h2 class="text-lg font-semibold">Session analytics</h2>
                    <p class="text-sm text-muted-foreground">
                        {{ sessionAnalytics.length }} session metric row{{
                            sessionAnalytics.length !== 1 ? 's' : ''
                        }}
                        available.
                    </p>
                </CardContent>
            </Card>

            <Card class="border-border/70 py-0 shadow-sm">
                <CardContent class="space-y-4 p-6">
                    <h2 class="text-lg font-semibold">Booth performance</h2>
                    <p class="text-sm text-muted-foreground">
                        {{ boothPerformance.length }} booth metric row{{
                            boothPerformance.length !== 1 ? 's' : ''
                        }}
                        available.
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
