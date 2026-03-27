<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
    level: number
}>()

const opacity = computed(() => Math.min(props.level / 10, 1))
</script>

<template>
    <span
        v-if="level > 0"
        class="absolute inset-0 rounded-full animate-ping"
        :style="{ backgroundColor: `rgba(59, 130, 246, ${opacity * 0.4})`, animationDuration: `${2 - opacity}s` }"
    />
</template>
