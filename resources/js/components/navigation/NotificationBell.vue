<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import { index as fetchNotifications, count as fetchCount, read as markRead } from '@/routes/notifications';

type Notification = {
    id: string;
    type: string;
    priority: string;
    message: string;
    created_at: string;
    data: Record<string, unknown>;
};

const unreadCount = ref(0);
const notifications = ref<Notification[]>([]);
const open = ref(false);
const loading = ref(false);
let interval: ReturnType<typeof setInterval> | null = null;

async function loadCount() {
    try {
        const response = await fetch('/notifications/count');
        const data = await response.json();
        unreadCount.value = data.count;
    } catch {
        // silently fail
    }
}

async function loadNotifications() {
    loading.value = true;
    try {
        const response = await fetch('/notifications');
        const data = await response.json();
        notifications.value = data.data;
    } catch {
        // silently fail
    } finally {
        loading.value = false;
    }
}

async function handleMarkRead(id: string) {
    try {
        await fetch(`/notifications/${id}/read`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN='))?.split('=')[1]?.replace('%3D', '=') ?? '',
            },
            credentials: 'same-origin',
        });
        notifications.value = notifications.value.filter(n => n.id !== id);
        unreadCount.value = Math.max(0, unreadCount.value - 1);
    } catch {
        // silently fail
    }
}

function toggle() {
    open.value = !open.value;
    if (open.value) {
        loadNotifications();
    }
}

function formatTime(iso: string): string {
    const diff = Date.now() - new Date(iso).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return 'Gerade eben';
    if (mins < 60) return `vor ${mins} Min.`;
    const hours = Math.floor(mins / 60);
    if (hours < 24) return `vor ${hours} Std.`;
    return `vor ${Math.floor(hours / 24)} Tagen`;
}

async function handlePingBack(notification: Notification) {
    const senderId = notification.data?.sender_id as number | undefined;
    const eventSlug = (window as any).__eventSlug;
    if (!senderId || !eventSlug) return;

    try {
        const token = document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN='))?.split('=')[1]?.replace('%3D', '=') ?? '';
        await fetch(`/event/${eventSlug}/ping/${senderId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': token,
            },
            credentials: 'same-origin',
        });
        await handleMarkRead(notification.id);
        router.reload();
    } catch {
        // silently fail
    }
}

function handleClickOutside(e: MouseEvent) {
    const target = e.target as HTMLElement;
    if (!target.closest('[data-notification-panel]')) {
        open.value = false;
    }
}

onMounted(() => {
    loadCount();
    interval = setInterval(loadCount, 30000);
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    if (interval) clearInterval(interval);
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="relative" data-notification-panel>
        <button class="relative p-2" @click="toggle">
            <span class="text-xl">🔔</span>
            <span
                v-if="unreadCount > 0"
                class="absolute -top-0.5 right-0 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown panel -->
        <div
            v-if="open"
            class="absolute right-0 top-full z-50 mt-2 w-80 rounded-2xl border border-neutral-200 bg-white shadow-xl"
        >
            <div class="flex items-center justify-between border-b border-neutral-100 px-4 py-3">
                <h3 class="text-sm font-semibold text-neutral-900">Benachrichtigungen</h3>
                <span class="text-xs text-neutral-400">{{ unreadCount }} ungelesen</span>
            </div>

            <div class="max-h-80 overflow-y-auto">
                <div v-if="loading" class="py-8 text-center text-sm text-neutral-400">
                    Laden...
                </div>

                <div v-else-if="notifications.length === 0" class="py-8 text-center text-sm text-neutral-400">
                    Keine Benachrichtigungen
                </div>

                <div v-else>
                    <div
                        v-for="notification in notifications"
                        :key="notification.id"
                        class="flex items-start gap-3 border-b border-neutral-50 px-4 py-3 last:border-b-0"
                        :class="notification.type === 'ping' ? 'bg-indigo-50/30' : ''"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-neutral-800">{{ notification.message }}</p>
                            <div class="mt-1 flex items-center gap-2">
                                <p class="text-xs text-neutral-400">{{ formatTime(notification.created_at) }}</p>
                                <button
                                    v-if="notification.type === 'ping' && notification.data?.sender_id"
                                    class="rounded-full bg-indigo-600 px-2.5 py-1 text-[11px] font-semibold text-white transition hover:bg-indigo-700"
                                    @click="handlePingBack(notification)"
                                >
                                    👋 Zurückpingen
                                </button>
                            </div>
                        </div>
                        <button
                            class="shrink-0 text-xs text-neutral-400 transition hover:text-neutral-600"
                            @click="handleMarkRead(notification.id)"
                        >
                            ✕
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
