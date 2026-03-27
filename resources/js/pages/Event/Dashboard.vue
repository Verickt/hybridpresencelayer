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
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-border/50 text-left text-muted-foreground">
                                    <th class="pb-2 font-medium">Session</th>
                                    <th class="pb-2 font-medium text-center">Check-ins</th>
                                    <th class="pb-2 font-medium text-center">Reactions</th>
                                    <th class="pb-2 font-medium text-center">Questions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="session in sessionAnalytics" :key="session.id" class="border-b border-border/30">
                                    <td class="py-3 font-medium">{{ session.title }}</td>
                                    <td class="py-3 text-center">{{ session.check_ins_count }}</td>
                                    <td class="py-3 text-center">{{ session.reactions_count }}</td>
                                    <td class="py-3 text-center">{{ session.questions_count }}</td>
                                </tr>
                                <tr v-if="sessionAnalytics.length === 0">
                                    <td colspan="4" class="py-6 text-center text-muted-foreground">No sessions yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <Card class="border-border/70 py-0 shadow-sm">
                <CardContent class="space-y-4 p-6">
                    <h2 class="text-lg font-semibold">Booth performance</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-border/50 text-left text-muted-foreground">
                                    <th class="pb-2 font-medium">Booth</th>
                                    <th class="pb-2 font-medium">Company</th>
                                    <th class="pb-2 font-medium text-center">Visitors</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="booth in boothPerformance" :key="booth.id" class="border-b border-border/30">
                                    <td class="py-3 font-medium">{{ booth.name }}</td>
                                    <td class="py-3 text-muted-foreground">{{ booth.company }}</td>
                                    <td class="py-3 text-center">{{ booth.visitor_count }}</td>
                                </tr>
                                <tr v-if="boothPerformance.length === 0">
                                    <td colspan="3" class="py-6 text-center text-muted-foreground">No booths yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
