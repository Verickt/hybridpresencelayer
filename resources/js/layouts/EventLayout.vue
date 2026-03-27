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

onMounted(() => {
    if (!window.Echo || !currentUser.value?.id) return;

    window.Echo.private(`user.${currentUser.value.id}.notifications`)
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
