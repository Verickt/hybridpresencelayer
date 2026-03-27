<script setup lang="ts">
import BottomTabs from '@/components/navigation/BottomTabs.vue'
import NotificationBell from '@/components/navigation/NotificationBell.vue'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const event = computed(() => (page.props as any).event ?? {})
const eventName = computed(() => event.value.name ?? '')
const eventSlug = computed(() => event.value.slug ?? '')
</script>

<template>
    <div class="min-h-screen bg-neutral-50 pb-16">
        <!-- Top header -->
        <header class="sticky top-0 z-40 bg-white safe-area-pt">
            <div class="flex items-center justify-between px-4 py-3">
                <h1 class="text-xl font-bold truncate text-neutral-900">{{ eventName }}</h1>
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
