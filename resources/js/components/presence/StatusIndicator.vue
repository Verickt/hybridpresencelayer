<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';

const props = defineProps<{
    status: string;
}>();

const statusConfig: Record<string, { color: string; label: string }> = {
    available: { color: 'bg-emerald-500', label: 'Available' },
    in_session: { color: 'bg-violet-500', label: 'In Session' },
    at_booth: { color: 'bg-sky-500', label: 'At Booth' },
    busy: { color: 'bg-amber-500', label: 'Busy' },
    away: { color: 'bg-zinc-400', label: 'Away' },
};

const config = computed(() => statusConfig[props.status] ?? statusConfig.away);
</script>

<template>
    <Badge
        variant="outline"
        class="gap-2 rounded-full border-transparent bg-muted/80 px-2.5 py-1 text-[11px] font-semibold tracking-[0.12em] text-muted-foreground uppercase"
    >
        <span class="h-2 w-2 rounded-full" :class="config.color" />
        <span>{{ config.label }}</span>
    </Badge>
</template>
