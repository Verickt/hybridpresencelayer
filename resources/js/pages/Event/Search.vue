<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Globe, MapPin, SearchIcon } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import StatusIndicator from '@/components/presence/StatusIndicator.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { ping } from '@/routes/event';

type SearchResult = {
    id: number;
    name: string;
    company: string;
    participant_type: string;
    status: string;
};

const props = defineProps<{
    event: { id: number; name: string; slug: string };
    query: string;
    results: SearchResult[];
}>();

const searchTerm = ref(props.query);

let debounceTimer: ReturnType<typeof setTimeout>;

watch(searchTerm, (value) => {
    clearTimeout(debounceTimer);

    debounceTimer = setTimeout(() => {
        router.reload({
            data: { q: value },
            only: ['query', 'results'],
            preserveState: true,
        });
    }, 300);
});

function handlePing(userId: number) {
    router.post(
        ping({ event: props.event.slug, user: userId }).url,
        {},
        { preserveState: true, preserveScroll: true },
    );
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
        <Head :title="`${event.name} - Search`" />

        <Heading
            title="Search participants"
            :description="`Find people by name, company, or interest at ${event.name}.`"
        />

        <div class="relative">
            <SearchIcon
                class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
            />
            <input
                v-model="searchTerm"
                type="text"
                placeholder="Search by name, company, or interest..."
                class="w-full rounded-xl border border-input bg-background py-2.5 pl-10 pr-4 text-sm shadow-xs transition outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
            />
        </div>

        <div v-if="query && results.length === 0">
            <Card class="border-dashed border-border/70 bg-card/80 py-0 shadow-sm">
                <CardContent class="py-12 text-center">
                    <p class="font-medium">No participants found.</p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Try a different search term.
                    </p>
                </CardContent>
            </Card>
        </div>

        <div v-else-if="results.length > 0" class="space-y-3">
            <Card
                v-for="result in results"
                :key="result.id"
                class="rounded-2xl border-border/70 bg-card/95 py-0 shadow-sm transition hover:border-primary/20"
            >
                <CardContent class="p-4">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary"
                            >
                                {{ result.name.charAt(0) }}
                            </div>

                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="truncate font-medium">
                                        {{ result.name }}
                                    </p>
                                    <Badge
                                        variant="outline"
                                        class="gap-1 rounded-full border-primary/15 bg-primary/5 px-2 py-0.5 text-[10px] font-medium text-primary"
                                    >
                                        <component
                                            :is="result.participant_type === 'physical' ? MapPin : Globe"
                                            class="size-3"
                                        />
                                        {{ result.participant_type === 'physical' ? 'Physical' : 'Remote' }}
                                    </Badge>
                                </div>
                                <p
                                    v-if="result.company"
                                    class="truncate text-sm text-muted-foreground"
                                >
                                    {{ result.company }}
                                </p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-3">
                            <StatusIndicator :status="result.status" />

                            <Button
                                size="sm"
                                class="rounded-full"
                                @click="handlePing(result.id)"
                            >
                                Ping
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
