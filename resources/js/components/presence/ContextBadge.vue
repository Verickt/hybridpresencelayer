<script setup lang="ts">
defineProps<{
    badge: string | null
}>()
</script>

<template>
    <span v-if="badge" class="inline-flex items-center rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium text-muted-foreground">
        {{ badge }}
    </span>
</template>
