<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import ParticipantCard from '@/components/presence/ParticipantCard.vue'
import PresenceFilters from '@/components/presence/PresenceFilters.vue'

const props = defineProps<{
    event: { id: number; name: string; slug: string }
    participants: Array<{
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
    }>
    filters: { type?: string; status?: string; tag?: string }
}>()

function handlePing(userId: number) {
    console.log('Ping user:', userId)
}
</script>

<template>
    <AppLayout :title="event.name">
        <div class="mx-auto max-w-2xl px-4 py-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold">{{ event.name }}</h1>
                <p class="text-muted-foreground">
                    {{ participants.length }} participant{{ participants.length !== 1 ? 's' : '' }} active
                </p>
            </div>

            <PresenceFilters :filters="filters" :event-slug="event.slug" />

            <div class="mt-6 space-y-3">
                <ParticipantCard
                    v-for="participant in participants"
                    :key="participant.id"
                    :participant="participant"
                    @ping="handlePing"
                />

                <p v-if="participants.length === 0" class="py-12 text-center text-muted-foreground">
                    No participants match your filters.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
