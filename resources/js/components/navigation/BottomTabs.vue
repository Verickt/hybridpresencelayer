<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    eventSlug: string;
}>();

const page = usePage();
const currentUrl = computed(() => page.url);
const base = computed(() => `/event/${props.eventSlug}`);
const today = new Date().getDate();

const tabs = computed(() => [
    {
        name: 'Feed',
        href: `${base.value}/feed`,
        active: currentUrl.value.includes('/feed'),
        icon: 'feed',
    },
    {
        name: 'Sessions',
        href: `${base.value}/sessions`,
        active: currentUrl.value.includes('/sessions'),
        icon: 'sessions',
        badge: String(today),
    },
    {
        name: 'Connections',
        href: `${base.value}/connections`,
        active: currentUrl.value.includes('/connections'),
        icon: 'connections',
    },
    {
        name: 'Profile',
        href: `${base.value}/profile`,
        active: currentUrl.value.includes('/profile'),
        icon: 'profile',
    },
]);
</script>

<template>
    <nav class="fixed bottom-0 left-0 right-0 z-50 border-t border-neutral-100 bg-white safe-area-pb">
        <div class="flex items-center justify-around py-1">
            <Link
                v-for="tab in tabs"
                :key="tab.name"
                :href="tab.href"
                class="relative flex min-w-[64px] flex-col items-center gap-0.5 px-3 py-2 text-[11px] font-medium transition-colors"
                :class="tab.active ? 'text-indigo-600' : 'text-neutral-400'"
            >
                <!-- Feed icon -->
                <svg v-if="tab.icon === 'feed'" class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                </svg>

                <!-- Sessions icon (calendar with date) -->
                <div v-else-if="tab.icon === 'sessions'" class="relative">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                        <line x1="16" y1="2" x2="16" y2="6" />
                        <line x1="8" y1="2" x2="8" y2="6" />
                        <line x1="3" y1="10" x2="21" y2="10" />
                    </svg>
                    <span
                        v-if="tab.badge"
                        class="absolute -right-2 -top-1 flex size-4 items-center justify-center rounded bg-red-500 text-[8px] font-bold text-white"
                    >
                        {{ tab.badge }}
                    </span>
                </div>

                <!-- Connections icon -->
                <svg v-else-if="tab.icon === 'connections'" class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                </svg>

                <!-- Profile icon -->
                <svg v-else-if="tab.icon === 'profile'" class="size-6" viewBox="0 0 24 24" :fill="tab.active ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                </svg>

                <span>{{ tab.name }}</span>
            </Link>
        </div>
    </nav>
</template>
