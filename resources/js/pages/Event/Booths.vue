<script setup lang="ts">
defineProps<{
    event: { id: number; name: string; slug: string }
    booths: Array<{
        id: number
        name: string
        company: string
        description: string
        interest_tags: string[]
        visitor_count: number
        staff: Array<{ id: number; name: string }>
        relevance: number
    }>
}>()
</script>

<template>
    <div>
        <h1>Booths</h1>
        <div v-for="booth in booths" :key="booth.id">
            <h2>{{ booth.name }}</h2>
            <p>{{ booth.company }}</p>
        </div>
    </div>
</template>
