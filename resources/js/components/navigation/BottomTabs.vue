<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { Users, Calendar, MessageCircle, UserCircle } from 'lucide-vue-next'

const props = defineProps<{
    eventSlug: string
}>()

const page = usePage()
const currentUrl = computed(() => page.url)

const tabs = computed(() => [
    {
        name: 'Feed',
        icon: Users,
        href: route('event.feed', { event: props.eventSlug }),
        active: currentUrl.value.includes('/feed'),
    },
    {
        name: 'Sessions',
        icon: Calendar,
        href: route('event.sessions', { event: props.eventSlug }),
        active: currentUrl.value.includes('/sessions'),
    },
    {
        name: 'Connections',
        icon: MessageCircle,
        href: route('event.connections', { event: props.eventSlug }),
        active: currentUrl.value.includes('/connections'),
    },
    {
        name: 'Profile',
        icon: UserCircle,
        href: route('event.profile', { event: props.eventSlug }),
        active: currentUrl.value.includes('/profile'),
    },
])
</script>

<template>
    <nav class="fixed bottom-0 left-0 right-0 z-50 border-t bg-background safe-area-pb">
        <div class="flex items-center justify-around">
            <a
                v-for="tab in tabs"
                :key="tab.name"
                :href="tab.href"
                class="flex flex-col items-center gap-0.5 px-3 py-2 text-xs transition-colors min-w-[64px]"
                :class="tab.active ? 'text-primary' : 'text-muted-foreground'"
            >
                <component :is="tab.icon" class="h-5 w-5" />
                <span>{{ tab.name }}</span>
            </a>
        </div>
    </nav>
</template>
