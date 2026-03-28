<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Zap } from 'lucide-vue-next';
import ParticipantAvatar from '@/components/presence/ParticipantAvatar.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

type Suggestion = {
    id: number;
    user: {
        id: number;
        name: string;
        participant_type: string | null;
    };
    score: number;
    reason: string;
};

const props = defineProps<{
    event: { id: number; name: string; slug: string };
    session: { id: number; title: string };
    suggestions: Suggestion[];
}>();

const pinging = new Set<number>();

function sendPing(suggestion: Suggestion) {
    if (pinging.has(suggestion.id)) {
        return;
    }

    pinging.add(suggestion.id);

    router.post(
        `/event/${props.event.slug}/ping/${suggestion.user.id}`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                pinging.delete(suggestion.id);
                router.reload({ only: ['suggestions'] });
            },
        },
    );
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Head :title="`People you vibed with — ${session.title}`" />

        <!-- Back link -->
        <Link
            :href="`/event/${event.slug}/sessions/${session.id}`"
            class="flex items-center gap-1.5 text-sm text-neutral-500 hover:text-neutral-700"
        >
            <ArrowLeft class="size-4" />
            Back to session
        </Link>

        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">People you vibed with</h1>
            <p class="mt-0.5 text-sm text-neutral-500">{{ session.title }}</p>
        </div>

        <!-- Empty state -->
        <Card
            v-if="suggestions.length === 0"
            class="border-dashed border-neutral-200 bg-neutral-50 py-0 shadow-none"
        >
            <CardContent class="py-12 text-center">
                <p class="font-medium text-neutral-700">No matches yet — check back soon</p>
                <p class="mt-1 text-sm text-neutral-400">
                    Session affinity matches appear shortly after a session ends.
                </p>
            </CardContent>
        </Card>

        <!-- Suggestion cards -->
        <div v-else class="space-y-3">
            <Card
                v-for="suggestion in suggestions"
                :key="suggestion.id"
                class="rounded-2xl border-neutral-100 bg-white py-0 shadow-sm"
            >
                <CardContent class="p-4">
                    <div class="flex items-start gap-4">
                        <ParticipantAvatar :name="suggestion.user.name" :interest-tags="[]" />

                        <div class="min-w-0 flex-1 space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="truncate text-base font-semibold text-neutral-900">
                                    {{ suggestion.user.name }}
                                </h3>

                                <Badge
                                    class="shrink-0 rounded-full px-2.5 py-0.5 text-[11px] font-medium"
                                    :class="
                                        suggestion.user.participant_type === 'physical'
                                            ? 'bg-indigo-50 text-indigo-700'
                                            : 'bg-green-50 text-green-700'
                                    "
                                >
                                    {{
                                        suggestion.user.participant_type === 'physical'
                                            ? 'In the room'
                                            : 'Remote'
                                    }}
                                </Badge>
                            </div>

                            <p class="text-sm text-neutral-500">{{ suggestion.reason }}</p>

                            <div class="flex justify-end">
                                <Button
                                    size="sm"
                                    class="rounded-full"
                                    :disabled="pinging.has(suggestion.id)"
                                    @click="sendPing(suggestion)"
                                >
                                    <Zap class="mr-1 size-3.5" />
                                    Ping
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
