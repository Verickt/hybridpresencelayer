<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';
import ActivityPulse from './ActivityPulse.vue';

const props = defineProps<{
    name: string;
    interestTags: string[];
    activityLevel?: number;
    size?: 'sm' | 'md' | 'lg';
}>();

const sizeClasses: Record<string, string> = {
    sm: 'size-8 text-xs',
    md: 'size-10 text-sm',
    lg: 'size-12 text-base',
};

const avatarColors = [
    'bg-teal-100 text-teal-700',
    'bg-indigo-100 text-indigo-700',
    'bg-rose-100 text-rose-700',
    'bg-amber-100 text-amber-700',
    'bg-sky-100 text-sky-700',
    'bg-emerald-100 text-emerald-700',
];

const initials = computed(() => getInitials(props.name));

const colorClass = computed(() => {
    const hash = props.name.split('').reduce((acc, c) => acc + c.charCodeAt(0), 0);
    return avatarColors[hash % avatarColors.length];
});
</script>

<template>
    <Avatar class="relative shrink-0" :class="sizeClasses[size ?? 'md']">
        <AvatarFallback
            class="font-semibold"
            :class="colorClass"
        >
            {{ initials }}
        </AvatarFallback>
        <ActivityPulse v-if="activityLevel" :level="activityLevel" />
    </Avatar>
</template>
