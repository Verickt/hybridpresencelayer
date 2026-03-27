<script setup lang="ts">
import BottomTabs from '@/components/navigation/BottomTabs.vue'
import NotificationBell from '@/components/navigation/NotificationBell.vue'

defineProps<{
    eventName: string
    eventSlug: string
}>()
</script>

<template>
    <div class="min-h-screen bg-background pb-16">
        <!-- Top header -->
        <header class="sticky top-0 z-40 border-b bg-background/95 backdrop-blur safe-area-pt">
            <div class="flex items-center justify-between px-4 py-3">
                <h1 class="text-lg font-semibold truncate">{{ eventName }}</h1>
                <NotificationBell />
            </div>
        </header>

        <!-- Main content -->
        <main>
            <slot />
        </main>

        <!-- Bottom navigation -->
        <BottomTabs :event-slug="eventSlug" />
    </div>
</template>

<style>
.safe-area-pt {
    padding-top: env(safe-area-inset-top);
}

.safe-area-pb {
    padding-bottom: env(safe-area-inset-bottom);
}
</style>
