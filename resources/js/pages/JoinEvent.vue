<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';

const props = defineProps<{
    event: {
        name: string;
        slug: string;
        starts_at: string;
        ends_at: string;
        venue: string;
    } | null;
}>();

const form = useForm({
    email: '',
    event_slug: props.event?.slug ?? '',
    name: '',
});

const sent = ref(false);

onMounted(() => {
    document.documentElement.classList.remove('dark');
});

onUnmounted(() => {
    initializeTheme();
});

const formattedDate = computed(() => {
    if (!props.event) return '';
    const start = new Date(props.event.starts_at);
    const end = new Date(props.event.ends_at);
    const dateOpts: Intl.DateTimeFormatOptions = { month: 'short', day: 'numeric' };
    const timeOpts: Intl.DateTimeFormatOptions = { hour: '2-digit', minute: '2-digit' };
    return `${start.toLocaleDateString(undefined, dateOpts)} · ${start.toLocaleTimeString(undefined, timeOpts)}–${end.toLocaleTimeString(undefined, timeOpts)}`;
});

function submit() {
    form.post('/magic-link', {
        preserveState: true,
        onSuccess: () => {
            sent.value = true;
        },
    });
}
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-white px-6">
        <Head :title="event?.name ?? 'Join Event'" />

        <template v-if="event">
            <!-- Event branding -->
            <div class="flex size-16 items-center justify-center rounded-2xl bg-orange-600 text-2xl font-bold text-white">
                {{ event.name.charAt(0) }}
            </div>

            <h2 class="mt-4 text-xl font-bold text-neutral-900">{{ event.name }}</h2>
            <p class="text-sm text-neutral-500">
                {{ formattedDate }}<template v-if="event.venue"> · {{ event.venue }}</template>
            </p>

            <template v-if="!sent">
                <!-- Join form -->
                <h1 class="mt-8 text-center text-2xl font-bold text-neutral-900">
                    Join the conversation
                </h1>
                <p class="mt-2 text-center text-sm text-neutral-500">
                    Enter your email to get a magic link. No password needed.
                </p>

                <form class="mt-6 w-full max-w-sm space-y-3" @submit.prevent="submit">
                    <div>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Your name"
                            class="w-full rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-base outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-100"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">
                            {{ form.errors.name }}
                        </p>
                    </div>
                    <div>
                        <input
                            v-model="form.email"
                            type="email"
                            placeholder="your@email.com"
                            required
                            class="w-full rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-base outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-100"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-500">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="mt-3 w-full rounded-xl bg-orange-600 py-3 text-base font-semibold text-white transition hover:bg-orange-700 disabled:opacity-50"
                    >
                        Send Magic Link
                    </button>
                </form>

                <div class="mt-6 flex items-center gap-3">
                    <div class="h-px flex-1 bg-neutral-100" />
                    <span class="text-xs text-neutral-400">or scan your invitation QR code</span>
                    <div class="h-px flex-1 bg-neutral-100" />
                </div>
            </template>

            <template v-else>
                <!-- Success state -->
                <div class="mt-8 text-center">
                    <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-green-100">
                        <span class="text-3xl">✉️</span>
                    </div>
                    <h1 class="mt-4 text-2xl font-bold text-neutral-900">Check your email</h1>
                    <p class="mt-2 text-sm text-neutral-500">
                        We sent a magic link to <strong>{{ form.email }}</strong>. Click it to join the event.
                    </p>
                </div>
            </template>
        </template>

        <template v-else>
            <p class="text-neutral-500">No event is currently active.</p>
        </template>
    </div>
</template>
