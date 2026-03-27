<script setup lang="ts">
defineProps<{
    currentStep: number;
    totalSteps: number;
}>();
</script>

<template>
    <div class="flex gap-2">
        <div
            v-for="step in totalSteps"
            :key="step"
            class="h-1 flex-1 rounded-full transition-colors"
            :class="step <= currentStep ? 'bg-orange-600' : 'bg-neutral-200'"
        />
    </div>
</template>
