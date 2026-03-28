<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import QRCode from 'qrcode';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps<{
    event: {
        name: string;
        slug: string;
        starts_at: string;
        ends_at: string;
        venue: string;
    } | null;
    joinUrl: string | null;
}>();

const qrDataUrl = ref<string | null>(null);

onMounted(async () => {
    document.documentElement.classList.remove('dark');
    if (props.joinUrl) {
        qrDataUrl.value = await QRCode.toDataURL(props.joinUrl, {
            width: 280,
            margin: 2,
            color: { dark: '#171717', light: '#ffffff' },
        });
    }
});

onUnmounted(() => {
    // Theme restored by next page
});

const formattedDate = computed(() => {
    if (!props.event) return '';
    const start = new Date(props.event.starts_at);
    const end = new Date(props.event.ends_at);
    const dateOpts: Intl.DateTimeFormatOptions = { month: 'short', day: 'numeric' };
    const timeOpts: Intl.DateTimeFormatOptions = { hour: '2-digit', minute: '2-digit' };
    return `${start.toLocaleDateString(undefined, dateOpts)} · ${start.toLocaleTimeString(undefined, timeOpts)}–${end.toLocaleTimeString(undefined, timeOpts)}`;
});
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-white px-6">
        <Head :title="event?.name ?? 'Join Event'" />

        <template v-if="event">
            <div class="flex size-20 items-center justify-center rounded-2xl bg-orange-600 text-3xl font-bold text-white">
                {{ event.name.charAt(0) }}
            </div>

            <h1 class="mt-6 text-center text-3xl font-bold text-neutral-900">{{ event.name }}</h1>
            <p class="mt-1 text-sm text-neutral-500">
                {{ formattedDate }}<template v-if="event.venue"> · {{ event.venue }}</template>
            </p>

            <p class="mt-8 text-center text-lg font-medium text-neutral-700">
                Scan to join
            </p>

            <div class="mt-4 rounded-2xl border-2 border-neutral-100 p-4">
                <img
                    v-if="qrDataUrl"
                    :src="qrDataUrl"
                    alt="Scan to join event"
                    class="size-[280px]"
                />
                <div v-else class="flex size-[280px] items-center justify-center text-sm text-neutral-400">
                    Loading...
                </div>
            </div>

            <a
                :href="`/event/${event.slug}/join`"
                class="mt-8 text-sm font-medium text-orange-600 transition hover:text-orange-700"
            >
                Joining remotely? Tap here →
            </a>
        </template>

        <template v-else>
            <p class="text-neutral-500">No event is currently active.</p>
        </template>
    </div>
</template>
