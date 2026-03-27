<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
    status: string
}>()

const statusConfig: Record<string, { color: string; label: string }> = {
    available: { color: 'bg-green-500', label: 'Available' },
    in_session: { color: 'bg-purple-500', label: 'In Session' },
    at_booth: { color: 'bg-blue-500', label: 'At Booth' },
    busy: { color: 'bg-red-500', label: 'Busy' },
    away: { color: 'bg-gray-400', label: 'Away' },
}

const config = computed(() => statusConfig[props.status] ?? statusConfig.away)
</script>

<template>
    <span class="inline-flex items-center gap-1.5">
        <span class="h-2.5 w-2.5 rounded-full" :class="config.color" />
        <span class="text-xs text-muted-foreground">{{ config.label }}</span>
    </span>
</template>
