<script setup lang="ts">
import { Head, router, useHttp } from '@inertiajs/vue3';
import { Clock, Sparkles, ThumbsDown, ThumbsUp, Users } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import ParticipantAvatar from '@/components/presence/ParticipantAvatar.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useHaptics } from '@/composables/useHaptics';
import { accept, decline } from '@/routes/event/suggestions';

const { match: hapticMatch } = useHaptics();

type Suggestion = {
    id: number;
    suggested_user: {
        id: number;
        name: string;
        company: string;
        participant_type: string;
    };
    score: number;
    reason: string;
    expires_at: string;
};

const props = defineProps<{
    event: { id: number; name: string; slug: string };
    suggestions: Suggestion[];
}>();

const actionRequest = useHttp();

async function handleDecline(suggestion: Suggestion) {
    try {
        await actionRequest.submit(
            decline({ event: props.event.slug, suggestion: suggestion.id }),
        );

        router.reload({ only: ['suggestions'] });
    } catch {
        // silently fail
    }
}

async function handleAccept(suggestion: Suggestion) {
    hapticMatch();
    try {
        await actionRequest.submit(
            accept({ event: props.event.slug, suggestion: suggestion.id }),
        );

        router.reload({ only: ['suggestions'] });
    } catch {
        // silently fail
    }
}

function timeRemaining(expiresAt: string): string {
    const diff = new Date(expiresAt).getTime() - Date.now();

    if (diff <= 0) {
        return 'Expired';
    }

    const minutes = Math.floor(diff / 60_000);

    if (minutes < 60) {
        return `${minutes}m left`;
    }

    return `${Math.floor(minutes / 60)}h ${minutes % 60}m left`;
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
        <Head :title="`${event.name} - Suggestions`" />

        <Card
            class="overflow-hidden border-border/70 bg-gradient-to-br from-primary/8 via-card to-card py-0 shadow-sm"
        >
            <CardContent class="space-y-4 p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        title="People you should meet"
                        :description="`Curated suggestions based on shared interests at ${event.name}.`"
                    />

                    <Badge
                        class="rounded-full px-3 py-1 text-[11px] font-semibold tracking-[0.16em] uppercase"
                    >
                        <Sparkles class="mr-1 size-3" />
                        Discovery
                    </Badge>
                </div>

                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                    <Users class="size-4" />
                    {{ suggestions.length }} suggestion{{ suggestions.length !== 1 ? 's' : '' }} available
                </div>
            </CardContent>
        </Card>

        <div v-if="suggestions.length === 0">
            <Card class="border-dashed border-border/70 bg-card/80 py-0 shadow-sm">
                <CardContent class="py-12 text-center">
                    <p class="font-medium">No suggestions right now.</p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Check back later — new matches appear as more people join
                        the event.
                    </p>
                </CardContent>
            </Card>
        </div>

        <div v-else class="space-y-3">
            <Card
                v-for="suggestion in suggestions"
                :key="suggestion.id"
                class="rounded-2xl border-border/70 bg-card/95 py-0 shadow-sm transition hover:border-primary/20"
            >
                <CardContent class="p-4">
                    <div class="flex items-start gap-4">
                        <ParticipantAvatar
                            :name="suggestion.suggested_user.name"
                            :interest-tags="[]"
                        />

                        <div class="min-w-0 flex-1 space-y-3">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div class="min-w-0 space-y-1">
                                    <h3 class="truncate text-base font-semibold">
                                        {{ suggestion.suggested_user.name }}
                                    </h3>
                                    <p
                                        v-if="suggestion.suggested_user.company"
                                        class="text-sm text-muted-foreground"
                                    >
                                        {{ suggestion.suggested_user.company }}
                                    </p>
                                </div>

                                <Badge
                                    variant="outline"
                                    class="rounded-full border-primary/15 bg-primary/5 px-2.5 py-1 text-[11px] font-medium text-primary"
                                >
                                    {{
                                        suggestion.suggested_user.participant_type === 'physical'
                                            ? 'Physical'
                                            : 'Remote'
                                    }}
                                </Badge>
                            </div>

                            <p class="text-sm text-muted-foreground">
                                {{ suggestion.reason }}
                            </p>

                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                    <Clock class="size-3" />
                                    {{ timeRemaining(suggestion.expires_at) }}
                                </div>

                                <div class="flex gap-2">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        class="rounded-full"
                                        :disabled="actionRequest.processing"
                                        @click="handleDecline(suggestion)"
                                    >
                                        <ThumbsDown class="mr-1 size-3.5" />
                                        Pass
                                    </Button>
                                    <Button
                                        size="sm"
                                        class="rounded-full"
                                        :disabled="actionRequest.processing"
                                        @click="handleAccept(suggestion)"
                                    >
                                        <ThumbsUp class="mr-1 size-3.5" />
                                        Connect
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
