<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import StepProgress from '@/components/onboarding/StepProgress.vue';

const props = defineProps<{
    event: { id: number; name: string; slug: string };
    currentType: string | null;
    userName: string;
}>();

const form = useForm({
    participant_type: props.currentType ?? '',
});

function select(type: string) {
    form.participant_type = type;
}

function submit() {
    form.post(`/event/${props.event.slug}/onboarding/type`);
}
</script>

<template>
    <div class="flex h-fit flex-col bg-white px-6 pt-6 pb-8">
        <Head :title="`${event.name} - Wie nehmen Sie teil?`" />

        <StepProgress :current-step="1" :total-steps="5" />

        <div class="mt-8">
            <h1 class="text-2xl font-bold text-neutral-900">Willkommen, {{ userName.split(' ')[0] }}!</h1>
            <p class="mt-1 text-sm text-neutral-500">Wie nehmen Sie heute teil?</p>
        </div>

        <div class="mt-6 space-y-3">
            <button
                class="flex w-full items-center gap-4 rounded-2xl border-2 p-4 text-left transition"
                :class="form.participant_type === 'physical'
                    ? 'border-orange-600 bg-orange-50'
                    : 'border-neutral-200 bg-white'"
                @click="select('physical')"
            >
                <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-red-50 text-xl">
                    📍
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-neutral-900">Ich bin persönlich vor Ort</p>
                    <p class="text-sm text-neutral-500">Am Veranstaltungsort</p>
                </div>
                <div
                    v-if="form.participant_type === 'physical'"
                    class="flex size-6 items-center justify-center rounded-full bg-orange-600 text-white"
                >
                    ✓
                </div>
            </button>

            <button
                class="flex w-full items-center gap-4 rounded-2xl border-2 p-4 text-left transition"
                :class="form.participant_type === 'remote'
                    ? 'border-orange-600 bg-orange-50'
                    : 'border-neutral-200 bg-white'"
                @click="select('remote')"
            >
                <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-xl">
                    🌐
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-neutral-900">Ich nehme remote teil</p>
                    <p class="text-sm text-neutral-500">Über den Livestream</p>
                </div>
                <div
                    v-if="form.participant_type === 'remote'"
                    class="flex size-6 items-center justify-center rounded-full bg-orange-600 text-white"
                >
                    ✓
                </div>
            </button>
        </div>

        <p class="mt-4 text-center text-xs text-neutral-400">
            Sie können jederzeit während des Events wechseln
        </p>

        <div class="mt-auto pt-6">
            <button
                :disabled="!form.participant_type || form.processing"
                class="w-full rounded-xl bg-orange-600 py-3.5 text-base font-semibold text-white transition hover:bg-orange-700 disabled:opacity-40"
                @click="submit"
            >
                Weiter
            </button>
        </div>
    </div>
</template>
