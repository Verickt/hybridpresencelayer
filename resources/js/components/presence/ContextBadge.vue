<script setup lang="ts">
import { Badge } from '@/components/ui/badge';

defineProps<{
    badge: string | null;
}>();
</script>

<template>
    <Badge
        v-if="badge"
        variant="secondary"
        class="rounded-full px-2.5 py-1 text-[11px] font-medium"
    >
        {{ badge }}
    </Badge>
</template>
