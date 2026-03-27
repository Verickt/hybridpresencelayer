<script setup lang="ts">
import { computed } from 'vue'
import ActivityPulse from './ActivityPulse.vue'

const props = defineProps<{
    name: string
    interestTags: string[]
    activityLevel?: number
}>()

const initials = computed(() => {
    return props.name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2)
})

const tagColors: Record<string, string> = {
    'Zero Trust': '#3B82F6',
    'Cloud Migration': '#8B5CF6',
    'DevOps': '#F59E0B',
    'AI/ML': '#EF4444',
    'Cybersecurity': '#10B981',
    'Data Privacy': '#6366F1',
    'IoT': '#EC4899',
    'Blockchain': '#14B8A6',
}

const bgColor = computed(() => {
    const primaryTag = props.interestTags[0] ?? ''
    return tagColors[primaryTag] ?? '#6B7280'
})
</script>

<template>
    <div
        class="relative inline-flex items-center justify-center rounded-full text-white font-semibold"
        :style="{ backgroundColor: bgColor, width: '48px', height: '48px' }"
    >
        <span class="text-sm">{{ initials }}</span>
        <ActivityPulse v-if="activityLevel" :level="activityLevel" />
    </div>
</template>
