<script setup lang="ts">
defineProps<{
    event: { id: number; name: string; slug: string }
    sessions: Array<{
        id: number
        title: string
        description: string
        speaker: string
        room: string
        starts_at: string
        ends_at: string
        is_live: boolean
        qa_enabled: boolean
        attendee_count: number
    }>
}>()
</script>

<template>
    <div>
        <h1>{{ event.name }} — Sessions</h1>
        <div v-for="session in sessions" :key="session.id">
            <h2>{{ session.title }}</h2>
            <p>{{ session.speaker }} — {{ session.room }}</p>
        </div>
    </div>
</template>
