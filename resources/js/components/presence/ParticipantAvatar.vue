<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';
import ActivityPulse from './ActivityPulse.vue';

const props = defineProps<{
    name: string;
    interestTags: string[];
    activityLevel?: number;
}>();

const tagColors: Record<string, string> = {
    'Zero Trust': '#3B82F6',
    'Cloud Migration': '#8B5CF6',
    DevOps: '#F59E0B',
    'AI/ML': '#EF4444',
    Cybersecurity: '#10B981',
    'Data Privacy': '#6366F1',
    IoT: '#EC4899',
    Blockchain: '#14B8A6',
};

const initials = computed(() => getInitials(props.name));

const bgColor = computed(() => {
    const primaryTag = props.interestTags[0] ?? '';

    return tagColors[primaryTag] ?? '#6B7280';
});
</script>

<template>
    <Avatar class="relative size-12 shrink-0 ring-2 ring-background">
        <AvatarFallback
            class="text-sm font-semibold text-white"
            :style="{ backgroundColor: bgColor }"
        >
            {{ initials }}
        </AvatarFallback>
        <ActivityPulse v-if="activityLevel" :level="activityLevel" />
    </Avatar>
</template>
