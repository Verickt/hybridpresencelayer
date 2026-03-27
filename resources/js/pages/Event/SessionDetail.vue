<script setup lang="ts">
defineProps<{
    event: { id: number; name: string; slug: string }
    session: {
        id: number
        title: string
        description: string
        speaker: string
        room: string
        starts_at: string
        ends_at: string
        is_live: boolean
        qa_enabled: boolean
        reactions_enabled: boolean
    }
    participants: Array<{ id: number; name: string; participant_type: string }>
    questions: Array<{ id: number; body: string; user: { id: number; name: string }; votes_count: number }>
}>()
</script>

<template>
    <div>
        <h1>{{ session.title }}</h1>
        <p>{{ session.speaker }} — {{ session.room }}</p>
    </div>
</template>
