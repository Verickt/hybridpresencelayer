<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'

defineProps<{
    event: { id: number; name: string; slug: string }
    overview: Record<string, number>
    sessionAnalytics: Array<Record<string, unknown>>
    boothPerformance: Array<Record<string, unknown>>
}>()
</script>

<template>
    <AppLayout>
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold">{{ event.name }} — Dashboard</h1>
            <p class="text-muted mt-1">Organizer overview</p>
        </div>
    </AppLayout>
</template>
