<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';

const props = defineProps<{
    participant: {
        id: number;
        name: string;
        company?: string;
        role_title?: string;
        participant_type: string;
        status: string;
        context_badge: string | null;
        interest_tags: string[];
    };
    hasPingedYou?: boolean;
}>();

defineEmits<{
    ping: [userId: number];
    select: [userId: number];
}>();

const initials = computed(() => getInitials(props.participant.name));

const statusColors: Record<string, string> = {
    available: 'bg-green-500',
    in_session: 'bg-blue-500',
    at_booth: 'bg-purple-500',
    busy: 'bg-amber-500',
    away: 'bg-neutral-400',
};

const statusColor = computed(() => statusColors[props.participant.status] ?? 'bg-neutral-400');

const avatarColors = [
    'bg-rose-100 text-rose-700',
    'bg-orange-100 text-orange-700',
    'bg-emerald-100 text-emerald-700',
    'bg-amber-100 text-amber-700',
    'bg-sky-100 text-sky-700',
    'bg-purple-100 text-purple-700',
];

const avatarColor = computed(() => {
    const hash = props.participant.name.split('').reduce((acc, c) => acc + c.charCodeAt(0), 0);
    return avatarColors[hash % avatarColors.length];
});

const displayTags = computed(() => props.participant.interest_tags.slice(0, 3));
</script>

<template>
    <div
        :dusk="`presence-row-${participant.id}`"
        class="flex cursor-pointer items-center gap-3 border-b border-neutral-100 py-3 last:border-b-0"
        :class="hasPingedYou ? 'bg-indigo-50/50' : ''"
        @click="$emit('select', participant.id)"
    >
        <div class="relative shrink-0">
            <Avatar class="size-10">
                <AvatarFallback
                    class="text-sm font-semibold"
                    :class="avatarColor"
                >
                    {{ initials }}
                </AvatarFallback>
            </Avatar>
            <span
                class="absolute -bottom-0.5 -left-0.5 size-3 rounded-full border-2 border-white"
                :class="statusColor"
            />
        </div>

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                <span class="text-sm font-semibold text-neutral-900">
                    {{ participant.name }}
                </span>
                <span v-if="participant.role_title" class="text-xs text-neutral-500">
                    {{ participant.role_title }}
                </span>
            </div>
            <span
                v-if="hasPingedYou"
                class="mt-0.5 inline-block rounded bg-indigo-100 px-1.5 py-0.5 text-[11px] font-semibold text-indigo-700"
            >
                👋 Hat dich gepingt
            </span>
            <span
                v-else-if="participant.context_badge"
                class="mt-0.5 inline-block rounded bg-orange-50 px-1.5 py-0.5 text-[11px] font-medium text-orange-600"
            >
                {{ participant.context_badge }}
            </span>
            <div class="mt-0.5 flex flex-wrap gap-1">
                <span
                    v-for="t in displayTags"
                    :key="t"
                    class="rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-medium text-neutral-600"
                >
                    {{ t }}
                </span>
            </div>
        </div>

        <button
            v-if="hasPingedYou"
            class="shrink-0 rounded-full bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-indigo-700"
            @click.stop="$emit('ping', participant.id)"
        >
            👋 Zurückpingen
        </button>
        <button
            v-else
            class="shrink-0 text-xl"
            @click.stop="$emit('ping', participant.id)"
        >
            👋
        </button>
    </div>
</template>
