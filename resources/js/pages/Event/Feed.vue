<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'

defineProps<{
    event: { id: number; name: string; slug: string }
    participants: Array<Record<string, unknown>>
    filters: { type?: string; status?: string; tag?: string }
}>()
</script>

<template>
    <AppLayout :title="event.name">
        <div class="mx-auto max-w-2xl px-4 py-6">
            <h1 class="text-2xl font-bold">{{ event.name }}</h1>
            <p class="text-muted-foreground">
                {{ participants.length }} participant{{ participants.length !== 1 ? 's' : '' }} active
            </p>
        </div>
    </AppLayout>
</template>
