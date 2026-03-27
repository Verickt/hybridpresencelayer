<script setup lang="ts">
import { Globe, MapPin, Radio } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import ContextBadge from './ContextBadge.vue';
import ParticipantAvatar from './ParticipantAvatar.vue';
import StatusIndicator from './StatusIndicator.vue';

defineProps<{
    participant: {
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
    };
}>();

defineEmits<{
    ping: [userId: number];
}>();
</script>

<template>
    <Card
        :dusk="`presence-card-${participant.id}`"
        class="rounded-2xl border-border/70 bg-card/95 py-0 shadow-sm transition hover:border-primary/20"
    >
        <CardContent class="p-4">
            <div class="flex items-start gap-4">
                <ParticipantAvatar
                    :name="participant.name"
                    :interest-tags="participant.interest_tags"
                />

                <div class="min-w-0 flex-1 space-y-3">
                    <div
                        class="flex flex-wrap items-start justify-between gap-2"
                    >
                        <div class="min-w-0 space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="truncate text-base font-semibold">
                                    {{ participant.name }}
                                </h3>
                                <Badge
                                    variant="outline"
                                    class="gap-1.5 rounded-full border-primary/15 bg-primary/5 px-2.5 py-1 text-[11px] font-medium text-primary"
                                >
                                    <component
                                        :is="
                                            participant.participant_type ===
                                            'physical'
                                                ? MapPin
                                                : Globe
                                        "
                                        class="size-3.5"
                                    />
                                    {{
                                        participant.participant_type ===
                                        'physical'
                                            ? 'Physical'
                                            : 'Remote'
                                    }}
                                </Badge>
                            </div>

                            <p
                                v-if="participant.company"
                                class="text-sm text-muted-foreground"
                            >
                                {{
                                    participant.role_title
                                        ? `${participant.role_title} at `
                                        : ''
                                }}{{ participant.company }}
                            </p>
                        </div>

                        <StatusIndicator :status="participant.status" />
                    </div>

                    <ContextBadge :badge="participant.context_badge" />

                    <p
                        v-if="participant.intent"
                        class="text-sm text-muted-foreground"
                    >
                        {{ participant.intent }}
                    </p>

                    <div class="flex flex-wrap gap-1.5">
                        <Badge
                            v-if="participant.open_to_call"
                            variant="secondary"
                            class="gap-1.5 rounded-full px-2.5 py-1 text-[11px]"
                        >
                            <Radio class="size-3.5" />
                            Open to call
                        </Badge>

                        <Badge
                            v-for="tag in participant.interest_tags"
                            :key="tag"
                            variant="outline"
                            class="rounded-full border-primary/15 bg-primary/5 px-2.5 py-1 text-[11px] font-medium text-primary"
                        >
                            {{ tag }}
                        </Badge>
                    </div>

                    <div class="flex items-end justify-between gap-3">
                        <p
                            v-if="participant.icebreaker_answer"
                            class="line-clamp-2 text-sm text-muted-foreground italic"
                        >
                            "{{ participant.icebreaker_answer }}"
                        </p>

                        <Button
                            type="button"
                            size="sm"
                            class="ml-auto rounded-full"
                            @click="$emit('ping', participant.id)"
                        >
                            Ping
                        </Button>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
