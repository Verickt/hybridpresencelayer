<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import StepProgress from '@/components/onboarding/StepProgress.vue';

const props = defineProps<{
    event: { id: number; name: string; slug: string };
    user: {
        name: string;
        participant_type: string;
        interest_tags: string[];
    };
}>();

const initials = props.user.name
    .split(' ')
    .map((n) => n[0])
    .join('')
    .toUpperCase();
</script>

<template>
    <div class="flex h-fit flex-col items-center bg-white px-6 pt-6 pb-8">
        <Head :title="`${event.name} - Ready!`" />

        <StepProgress :current-step="5" :total-steps="5" class="w-full" />

        <div class="mt-16 flex flex-col items-center">
            <div class="flex size-24 items-center justify-center rounded-full bg-orange-100 text-3xl font-bold text-orange-700">
                {{ initials }}
            </div>

            <h1 class="mt-6 text-center text-2xl font-bold text-neutral-900">Alles bereit!</h1>
            <p class="mt-2 text-center text-sm text-neutral-500">
                {{ user.participant_type === 'physical' ? 'Vor-Ort' : 'Remote' }}-Teilnehmer interessiert an {{ user.interest_tags.join(', ') }}
            </p>
        </div>

        <div class="mt-10 flex flex-col items-center rounded-2xl bg-neutral-50 px-6 py-5">
            <div class="flex size-10 items-center justify-center rounded-full bg-orange-100 text-lg">📱</div>
            <p class="mt-3 text-center text-sm font-medium text-neutral-700">So vernetzen Sie sich</p>
            <p class="mt-1 text-center text-xs text-neutral-500">
                Treffen Sie jemanden? Öffnen Sie Ihr Profil und zeigen Sie Ihren QR-Code — die andere Person scannt ihn und Sie sind verbunden.
            </p>
        </div>

        <div class="mt-auto w-full pt-6">
            <Link
                :href="`/event/${event.slug}/feed`"
                class="flex w-full items-center justify-center rounded-xl bg-orange-600 py-3.5 text-base font-semibold text-white transition hover:bg-orange-700"
            >
                Event betreten →
            </Link>
        </div>
    </div>
</template>
