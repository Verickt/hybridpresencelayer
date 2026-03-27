<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import StepProgress from '@/components/onboarding/StepProgress.vue';

const props = defineProps<{
    event: { id: number; name: string; slug: string };
    questions: Array<{ id: number; text: string }>;
    currentAnswer: string | null;
}>();

const form = useForm({
    icebreaker_answer: props.currentAnswer ?? '',
});

function select(text: string) {
    form.icebreaker_answer = form.icebreaker_answer === text ? '' : text;
}

function submit() {
    form.post(`/event/${props.event.slug}/onboarding/icebreaker`);
}

function skip() {
    router.visit(`/event/${props.event.slug}/onboarding/ready`);
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-white px-6 pt-6 pb-8">
        <Head :title="`${event.name} - Icebreaker`" />

        <StepProgress :current-step="3" :total-steps="4" />

        <div class="mt-8">
            <h1 class="text-2xl font-bold text-neutral-900">Break the ice</h1>
            <p class="mt-1 text-sm text-neutral-500">
                Pick a question others can see on your profile. This helps start conversations.
            </p>
        </div>

        <div class="mt-6 space-y-3">
            <button
                v-for="question in questions"
                :key="question.id"
                class="flex w-full items-center gap-3 rounded-2xl border-2 p-4 text-left text-sm transition"
                :class="form.icebreaker_answer === question.text
                    ? 'border-orange-600 bg-orange-50'
                    : 'border-neutral-200 bg-white'"
                @click="select(question.text)"
            >
                <div
                    class="flex size-6 shrink-0 items-center justify-center rounded-full border-2 transition"
                    :class="form.icebreaker_answer === question.text
                        ? 'border-orange-600 bg-orange-600 text-white'
                        : 'border-neutral-300'"
                >
                    <span v-if="form.icebreaker_answer === question.text" class="text-xs">✓</span>
                </div>
                <span class="text-neutral-900">{{ question.text }}</span>
            </button>
        </div>

        <div class="mt-auto flex items-center gap-3 pt-6">
            <button
                class="px-6 py-3.5 text-sm font-medium text-neutral-500 transition hover:text-neutral-700"
                @click="skip"
            >
                Skip
            </button>
            <button
                :disabled="form.processing"
                class="flex-1 rounded-xl bg-orange-600 py-3.5 text-base font-semibold text-white transition hover:bg-orange-700 disabled:opacity-40"
                @click="submit"
            >
                Continue
            </button>
        </div>
    </div>
</template>
