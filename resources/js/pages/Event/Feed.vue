<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import ParticipantCard from '@/components/presence/ParticipantCard.vue'
import PresenceFilters from '@/components/presence/PresenceFilters.vue'
import { ref, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'

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

const liveParticipants = ref([...props.participants])

function handlePing(userId: number) {
    console.log('Ping user:', userId)
}

const lastOccurredAt = ref<Record<number, string>>({})

onMounted(() => {
    const channel = window.Echo.join(`event.${props.event.id}.presence`)

    channel
        .here(() => {
            // Initial presence list from Reverb
        })
        .joining((user: { id: number; name: string }) => {
            if (!liveParticipants.value.find(p => p.id === user.id)) {
                router.reload({ only: ['participants'] })
            }
        })
        .leaving(() => {
            // Don't remove — status will go to 'away' via server
        })

    channel.listen('PresenceStateChanged', (data: {
        user_id: number
        status: string
        context_badge: string | null
        participant_type: string
        occurred_at: string
    }) => {
        const last = lastOccurredAt.value[data.user_id]
        if (last && data.occurred_at < last) return
        lastOccurredAt.value[data.user_id] = data.occurred_at

        const participant = liveParticipants.value.find(p => p.id === data.user_id)
        if (participant) {
            participant.status = data.status
            participant.context_badge = data.context_badge
        }
    })
})

onUnmounted(() => {
    window.Echo.leave(`event.${props.event.id}.presence`)
})
</script>

<template>
    <AppLayout :title="event.name">
        <div class="mx-auto max-w-2xl px-4 py-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold">{{ event.name }}</h1>
                <p class="text-muted-foreground">
                    {{ liveParticipants.length }} participant{{ liveParticipants.length !== 1 ? 's' : '' }} active
                </p>
            </div>

            <PresenceFilters :filters="filters" :event-slug="event.slug" />

            <div class="mt-6 space-y-3">
                <ParticipantCard
                    v-for="participant in liveParticipants"
                    :key="participant.id"
                    :participant="participant"
                    @ping="handlePing"
                />

                <p v-if="liveParticipants.length === 0" class="py-12 text-center text-muted-foreground">
                    No participants match your filters.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
