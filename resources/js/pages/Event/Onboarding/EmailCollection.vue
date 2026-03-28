<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import StepProgress from '@/components/onboarding/StepProgress.vue';

const props = defineProps<{
    event: { id: number; name: string; slug: string };
    currentEmail: string | null;
}>();

const form = useForm({
    email: props.currentEmail ?? '',
});

function submit() {
    form.post(`/event/${props.event.slug}/onboarding/email`);
}

function skip() {
    router.visit(`/event/${props.event.slug}/onboarding/ready`);
}
</script>

<template>
    <div class="flex h-fit flex-col bg-white px-6 pt-6 pb-8">
        <Head :title="`${event.name} - Email`" />

        <StepProgress :current-step="4" :total-steps="5" />

        <div class="mt-8">
            <h1 class="text-2xl font-bold text-neutral-900">Stay connected</h1>
            <p class="mt-1 text-sm text-neutral-500">
                Add your email so people can reach you after the event.
            </p>
        </div>

        <form class="mt-6 space-y-3" @submit.prevent="submit">
            <div>
                <input
                    v-model="form.email"
                    type="email"
                    placeholder="your@email.com"
                    autofocus
                    class="w-full rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-base outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-100"
                />
                <p v-if="form.errors.email" class="mt-1 text-sm text-red-500">
                    {{ form.errors.email }}
                </p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-xl bg-orange-600 py-3.5 text-base font-semibold text-white transition hover:bg-orange-700 disabled:opacity-40"
            >
                Continue
            </button>
        </form>

        <div class="mt-auto flex items-center gap-3 pt-6">
            <button
                class="w-full px-6 py-3.5 text-sm font-medium text-neutral-500 transition hover:text-neutral-700"
                @click="skip"
            >
                Skip
            </button>
        </div>
    </div>
</template>
