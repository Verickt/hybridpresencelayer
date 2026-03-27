<script setup lang="ts">
import ParticipantAvatar from './ParticipantAvatar.vue'
import StatusIndicator from './StatusIndicator.vue'
import ContextBadge from './ContextBadge.vue'
import { MapPin, Globe } from 'lucide-vue-next'

defineProps<{
    participant: {
        id: number
        name: string
        company?: string
        role_title?: string
        intent?: string
        participant_type: string
        status: string
        context_badge: string | null
        icebreaker_answer?: string
        open_to_call: boolean
        interest_tags: string[]
    }
}>()

defineEmits<{
    ping: [userId: number]
}>()
</script>

<template>
    <div class="flex items-start gap-4 rounded-xl border p-4 transition hover:bg-muted/50">
        <ParticipantAvatar
            :name="participant.name"
            :interest-tags="participant.interest_tags"
        />

        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
                <h3 class="font-semibold truncate">{{ participant.name }}</h3>
                <component
                    :is="participant.participant_type === 'physical' ? MapPin : Globe"
                    class="h-4 w-4 shrink-0 text-muted-foreground"
                />
                <StatusIndicator :status="participant.status" />
            </div>

            <p v-if="participant.company" class="text-sm text-muted-foreground">
                {{ participant.role_title ? `${participant.role_title} at ` : '' }}{{ participant.company }}
            </p>

            <ContextBadge :badge="participant.context_badge" />

            <div class="mt-2 flex flex-wrap gap-1.5">
                <span
                    v-for="tag in participant.interest_tags"
                    :key="tag"
                    class="inline-flex items-center rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
                >
                    {{ tag }}
                </span>
            </div>

            <p v-if="participant.icebreaker_answer" class="mt-2 text-sm italic text-muted-foreground">
                "{{ participant.icebreaker_answer }}"
            </p>
        </div>

        <button
            @click="$emit('ping', participant.id)"
            class="shrink-0 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
        >
            Ping
        </button>
    </div>
</template>
