<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Trash2, Users } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import ParticipantController from '@/actions/App/Http/Controllers/ParticipantController';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    event: { id: number; name: string; slug: string };
    participants: Array<{
        id: number;
        name: string;
        email: string | null;
        pivot: {
            participant_type: string;
            status: string;
            last_active_at: string | null;
        };
    }>;
}>();

const confirmingDelete = ref<number | null>(null);

function deleteParticipant(userId: number) {
    router.delete(ParticipantController.destroy.url({ event: props.event.slug, user: userId }), {
        preserveScroll: true,
        onSuccess: () => { confirmingDelete.value = null; },
    });
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head :title="`Teilnehmer — ${event.name}`" />

        <div class="flex items-center gap-3">
            <Users class="size-5 text-indigo-600" />
            <h1 class="text-2xl font-bold">Teilnehmer</h1>
            <span class="text-sm text-muted-foreground">({{ participants.length }})</span>
        </div>

        <Card class="border-border/70 py-0 shadow-sm">
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border/50 text-left text-muted-foreground">
                                <th class="px-4 py-3 font-medium">Name</th>
                                <th class="px-4 py-3 font-medium">E-Mail</th>
                                <th class="px-4 py-3 font-medium text-center">Typ</th>
                                <th class="px-4 py-3 font-medium text-center">Status</th>
                                <th class="px-4 py-3 font-medium text-right">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="participant in participants"
                                :key="participant.id"
                                class="border-b border-border/30"
                            >
                                <td class="px-4 py-3 font-medium">{{ participant.name }}</td>
                                <td class="px-4 py-3 text-muted-foreground">{{ participant.email ?? '—' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="participant.pivot.participant_type === 'physical'
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                            : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'"
                                    >
                                        {{ participant.pivot.participant_type === 'physical' ? 'Vor Ort' : 'Remote' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center text-muted-foreground">{{ participant.pivot.status }}</td>
                                <td class="px-4 py-3 text-right">
                                    <Button
                                        v-if="confirmingDelete !== participant.id"
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive hover:text-destructive"
                                        @click="confirmingDelete = participant.id"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                    <div v-else class="flex items-center justify-end gap-1">
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            @click="deleteParticipant(participant.id)"
                                        >
                                            Löschen
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="confirmingDelete = null"
                                        >
                                            Abbrechen
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="participants.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                                    Noch keine Teilnehmer.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
