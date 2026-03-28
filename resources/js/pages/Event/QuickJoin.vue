<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    event: { id: number; name: string; slug: string; venue?: string };
}>();

const form = useForm({
    name: '',
});

function submit() {
    form.post(`/event/${props.event.slug}/join`);
}
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-white px-6">
        <Head :title="`${event.name} - Join`" />

        <div class="flex size-16 items-center justify-center rounded-2xl bg-orange-600 text-2xl font-bold text-white">
            {{ event.name.charAt(0) }}
        </div>

        <h2 class="mt-4 text-xl font-bold text-neutral-900">{{ event.name }}</h2>
        <p v-if="event.venue" class="text-sm text-neutral-500">{{ event.venue }}</p>

        <h1 class="mt-8 text-center text-2xl font-bold text-neutral-900">
            What's your name?
        </h1>
        <p class="mt-2 text-center text-sm text-neutral-500">
            Enter your name to join the event.
        </p>

        <form class="mt-6 w-full max-w-sm space-y-3" @submit.prevent="submit">
            <div>
                <input
                    v-model="form.name"
                    type="text"
                    placeholder="Your name"
                    autofocus
                    class="w-full rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-base outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-100"
                />
                <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">
                    {{ form.errors.name }}
                </p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-xl bg-orange-600 py-3 text-base font-semibold text-white transition hover:bg-orange-700 disabled:opacity-50"
            >
                Join Event
            </button>
        </form>
    </div>
</template>
