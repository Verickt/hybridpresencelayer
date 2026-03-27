<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Compass, Sparkles, Users } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import Heading from '@/components/Heading.vue';
import { show } from '@/routes/event/booths';

const props = defineProps<{
    event: { id: number; name: string; slug: string };
    booths: Array<{
        id: number;
        name: string;
        company: string;
        description: string;
        interest_tags: string[];
        visitor_count: number;
        staff: Array<{ id: number; name: string }>;
        relevance: number;
    }>;
}>();
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
        <Head :title="`${event.name} Booths`" />

        <Card
            class="border-border/70 bg-gradient-to-br from-primary/8 via-card to-card py-0 shadow-sm"
        >
            <CardContent class="space-y-3 p-6">
                <Heading
                    title="Booths"
                    :description="`${booths.length} booths currently listed for ${event.name}.`"
                />
                <p class="text-sm text-muted-foreground">
                    Shared cards, badges, and spacing aligned with the app-wide
                    component system, without changing booth workflows.
                </p>
            </CardContent>
        </Card>

        <div class="space-y-3">
            <Card
                v-for="booth in booths"
                :key="booth.id"
                :dusk="`booth-card-${booth.id}`"
                class="border-border/70 py-0 shadow-sm"
            >
                <CardContent class="space-y-4 p-6">
                    <div
                        class="flex flex-wrap items-start justify-between gap-3"
                    >
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-lg font-semibold">
                                    {{ booth.name }}
                                </h2>
                                <Badge
                                    v-if="booth.relevance > 0"
                                    class="rounded-full px-2.5 py-1 text-[11px] font-semibold tracking-[0.12em] uppercase"
                                >
                                    {{ booth.relevance }} match{{
                                        booth.relevance !== 1 ? 'es' : ''
                                    }}
                                </Badge>
                            </div>

                            <p class="text-sm text-muted-foreground">
                                {{ booth.company }}
                            </p>

                            <p
                                v-if="booth.description"
                                class="text-sm text-muted-foreground"
                            >
                                {{ booth.description }}
                            </p>
                        </div>

                        <Button as-child variant="outline" class="rounded-full">
                            <Link
                                :href="
                                    show({
                                        event: props.event.slug,
                                        booth: booth.id,
                                    })
                                "
                            >
                                View
                            </Link>
                        </Button>
                    </div>

                    <div
                        class="grid gap-3 text-sm text-muted-foreground md:grid-cols-2"
                    >
                        <div class="flex items-center gap-2">
                            <Users class="size-4" />
                            {{ booth.visitor_count }} visitor{{
                                booth.visitor_count !== 1 ? 's' : ''
                            }}
                        </div>
                        <div class="flex items-center gap-2">
                            <Compass class="size-4" />
                            {{ booth.staff.length }} staff
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-1.5">
                        <Badge
                            v-for="tag in booth.interest_tags"
                            :key="tag"
                            variant="outline"
                            class="rounded-full border-primary/15 bg-primary/5 px-2.5 py-1 text-[11px] font-medium text-primary"
                        >
                            <Sparkles class="size-3.5" />
                            {{ tag }}
                        </Badge>
                    </div>
                </CardContent>
            </Card>

            <Card
                v-if="booths.length === 0"
                class="border-dashed border-border/70 py-0 shadow-sm"
            >
                <CardContent class="py-12 text-center">
                    <p class="font-medium">No booths are listed yet.</p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Booth discovery will appear here once exhibitors are
                        added to the event.
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
