<script setup lang="ts">
import { ref, onMounted } from 'vue'

const unreadCount = ref(0)

async function fetchCount() {
    try {
        const response = await fetch('/notifications/count')
        const data = await response.json()
        unreadCount.value = data.count
    } catch {
        // Silently fail
    }
}

onMounted(() => {
    fetchCount()
    setInterval(fetchCount, 30000)
})
</script>

<template>
    <button class="relative p-2" @click="$emit('open')">
        <span class="text-xl">🔔</span>
        <span
            v-if="unreadCount > 0"
            class="absolute -top-0.5 right-0 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white"
        >
            {{ unreadCount > 9 ? '9+' : unreadCount }}
        </span>
    </button>
</template>
