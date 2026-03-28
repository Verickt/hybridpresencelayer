<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import MutualMatchModal from '@/components/match/MutualMatchModal.vue';
import BottomTabs from '@/components/navigation/BottomTabs.vue';
import NotificationBell from '@/components/navigation/NotificationBell.vue';

const page = usePage();
const event = computed(() => (page.props as any).event ?? {});
const eventName = computed(() => event.value.name ?? '');
const eventSlug = computed(() => event.value.slug ?? '');
const currentUser = computed(() => (page.props as any).auth?.user ?? {});

// Mutual match modal state
const matchModalOpen = ref(false);
const matchData = ref<{
    connectionId: number;
    user: { name: string; role_title?: string; company?: string };
    sharedTags: string[];
    icebreaker?: string;
} | null>(null);

// Ping notification state
const pingNotification = ref<{ senderName: string } | null>(null);
let pingTimeout: ReturnType<typeof setTimeout> | undefined;

function showPingNotification(senderName: string) {
    clearTimeout(pingTimeout);
    pingNotification.value = { senderName };
    pingTimeout = setTimeout(() => {
        pingNotification.value = null;
    }, 5000);
}

// Force light mode for event pages — they use hardcoded light colors
const wasDark = ref(false);
onMounted(() => {
    wasDark.value = document.documentElement.classList.contains('dark');
    document.documentElement.classList.remove('dark');
    (window as any).__eventSlug = eventSlug.value;

    if (!window.Echo || !currentUser.value?.id) return;

    window.Echo.private(`user.${currentUser.value.id}.notifications`)
        .listen('PingReceived', (data: any) => {
            showPingNotification(data.sender?.name ?? 'Someone');
        })
        .listen('MutualMatchCreated', (data: any) => {
            const otherUser = data.user_a.id === currentUser.value.id
                ? data.user_b
                : data.user_a;

            matchData.value = {
                connectionId: data.connection_id,
                user: { name: otherUser.name },
                sharedTags: data.shared_tags ?? [],
                icebreaker: data.icebreaker ?? undefined,
            };
            matchModalOpen.value = true;
        });
});

onUnmounted(() => {
    // Restore dark mode if it was active before entering event pages
    if (wasDark.value) {
        document.documentElement.classList.add('dark');
    }

    if (currentUser.value?.id) {
        window.Echo?.leave(`user.${currentUser.value.id}.notifications`);
    }
});
</script>

<template>
    <div class="min-h-screen bg-neutral-50 pb-16">
        <!-- Top header -->
        <header class="sticky top-0 z-40 bg-white safe-area-pt">
            <div class="flex items-center justify-between px-4 py-3">
                <h1 class="truncate text-xl font-bold text-neutral-900">{{ eventName }}</h1>
                <NotificationBell />
            </div>
        </header>

        <!-- Ping notification toast -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="-translate-y-full opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="-translate-y-full opacity-0"
        >
            <div
                v-if="pingNotification"
                class="fixed inset-x-0 top-0 z-50 flex items-center justify-center px-4 pt-[calc(env(safe-area-inset-top)+4px)]"
            >
                <div class="flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-3 text-sm font-medium text-white shadow-lg">
                    <span class="text-lg">👋</span>
                    <span><strong>{{ pingNotification.senderName }}</strong> pinged you!</span>
                    <button class="ml-2 text-white/70 hover:text-white" @click="pingNotification = null">✕</button>
                </div>
            </div>
        </Transition>

        <!-- Main content -->
        <main>
            <slot />
        </main>

        <!-- Bottom navigation -->
        <BottomTabs :event-slug="eventSlug" />

        <!-- Mutual match celebration -->
        <MutualMatchModal
            :open="matchModalOpen"
            :match="matchData"
            :current-user-name="currentUser?.name ?? ''"
            :event-slug="eventSlug"
            @close="matchModalOpen = false"
        />
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
