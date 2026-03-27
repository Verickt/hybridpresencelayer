<script setup lang="ts">
defineProps<{
    event: { id: number; name: string; slug: string }
    booth: {
        id: number
        name: string
        company: string
        description: string
        content_links: string[] | null
        interest_tags: string[]
    }
    visitors: Array<{
        id: number
        name: string
        company: string
        participant_type: string
        entered_at: string
    }>
    staff: Array<{
        id: number
        name: string
        status: string | null
    }>
}>()
</script>

<template>
    <div>
        <h1>{{ booth.name }}</h1>
        <p>{{ booth.company }}</p>
        <section>
            <h2>Visitors</h2>
            <div v-for="visitor in visitors" :key="visitor.id">
                {{ visitor.name }}
            </div>
        </section>
        <section>
            <h2>Staff</h2>
            <div v-for="member in staff" :key="member.id">
                {{ member.name }}
            </div>
        </section>
    </div>
</template>
