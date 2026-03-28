<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

type Participant = {
    id: number;
    name: string;
    email: string | null;
    created_at: string;
    pivot: {
        participant_type: string;
        status: string;
    };
};

defineProps<{
    event: { id: number; name: string; slug: string };
    participants: Participant[];
}>();
</script>

<template>
    <Head :title="`${event.name} — Participants`" />
    <div class="p-4">
        <h1 class="text-xl font-semibold mb-4">Participants</h1>
        <ul>
            <li v-for="participant in participants" :key="participant.id">
                {{ participant.name }}
            </li>
        </ul>
    </div>
</template>
