<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import StepProgress from '@/components/onboarding/StepProgress.vue';

const props = defineProps<{
    event: { id: number; name: string; slug: string };
    tags: Array<{ id: number; name: string }>;
    selectedIds: number[];
}>();

const form = useForm({
    tag_ids: [...props.selectedIds],
});

const remaining = computed(() => Math.max(0, 3 - form.tag_ids.length));
const canContinue = computed(() => form.tag_ids.length >= 3);

function toggle(tagId: number) {
    const idx = form.tag_ids.indexOf(tagId);
    if (idx >= 0) {
        form.tag_ids.splice(idx, 1);
    } else if (form.tag_ids.length < 5) {
        form.tag_ids.push(tagId);
    }
}

function submit() {
    form.post(`/event/${props.event.slug}/onboarding/tags`);
}
</script>

<template>
    <div class="flex h-fit flex-col bg-white px-6 pt-6 pb-8">
        <Head :title="`${event.name} - Interests`" />

        <StepProgress :current-step="2" :total-steps="5" />

        <div class="mt-8">
            <h1 class="text-2xl font-bold text-neutral-900">Was interessiert Sie?</h1>
            <p class="mt-1 text-sm text-neutral-500">Wählen Sie 3 Themen, damit wir Ihre Leute finden</p>
        </div>

        <p class="mt-4 text-sm font-medium text-orange-600">
            {{ form.tag_ids.length }} von 3 ausgewählt
        </p>

        <div class="mt-4 flex flex-wrap gap-2">
            <button
                v-for="tag in tags"
                :key="tag.id"
                class="rounded-full border px-4 py-2 text-sm font-medium transition"
                :class="form.tag_ids.includes(tag.id)
                    ? 'border-orange-600 bg-orange-600 text-white'
                    : 'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300'"
                @click="toggle(tag.id)"
            >
                {{ tag.name }}
            </button>
        </div>

        <div class="mt-auto pt-6">
            <button
                :disabled="!canContinue || form.processing"
                class="w-full rounded-xl bg-orange-600 py-3.5 text-base font-semibold text-white transition hover:bg-orange-700 disabled:opacity-40"
                @click="submit"
            >
                {{ canContinue ? 'Weiter' : `Noch ${remaining} auswählen` }}
            </button>
        </div>
    </div>
</template>
