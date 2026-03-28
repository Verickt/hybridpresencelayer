<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { MessageCircle, Phone } from 'lucide-vue-next';

type ConnectionItem = {
    connection_id: number;
    user: { id: number; name: string; company: string };
    context: string;
    is_cross_world: boolean;
    created_at: string;
};

const props = defineProps<{
    event: { id: number; name: string; slug: string };
    connections: ConnectionItem[];
}>();

function openChat(connectionId: number) {
    router.visit(`/event/${props.event.slug}/connections/${connectionId}/chat`);
}

function openCall(connectionId: number) {
    router.visit(`/event/${props.event.slug}/connections/${connectionId}/chat`);
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
        <Head :title="`${event.name} - Connections`" />

        <div class="space-y-1">
            <h1 class="text-2xl font-bold text-neutral-900">Verbindungen</h1>
            <p class="text-sm text-neutral-500">{{ connections.length }} Personen, mit denen Sie gematcht haben</p>
        </div>

        <!-- Connection list -->
        <div>
            <div
                v-for="connection in connections"
                :key="connection.connection_id"
                class="flex cursor-pointer items-center gap-3 border-b border-neutral-100 py-3 last:border-b-0"
                @click="openChat(connection.connection_id)"
            >
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-orange-100 text-sm font-semibold text-orange-700">
                    {{ connection.user.name.split(' ').map((n: string) => n[0]).join('') }}
                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-neutral-900">{{ connection.user.name }}</p>
                    <p class="text-xs text-neutral-500">
                        {{ connection.user.company }}{{ connection.context ? ` · ${connection.context}` : '' }}
                    </p>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <button
                        class="flex size-9 items-center justify-center rounded-full border border-neutral-200 text-neutral-500 transition hover:bg-neutral-50"
                        @click.stop="openChat(connection.connection_id)"
                    >
                        <MessageCircle class="size-4" />
                    </button>
                    <button
                        class="flex size-9 items-center justify-center rounded-full border border-neutral-200 text-neutral-500 transition hover:bg-neutral-50"
                        @click.stop="openCall(connection.connection_id)"
                    >
                        <Phone class="size-4" />
                    </button>
                </div>
            </div>
        </div>

        <p v-if="connections.length === 0" class="py-8 text-center text-sm text-neutral-400">
            Noch keine Verbindungen. Lernen Sie neue Leute kennen!
        </p>
    </div>
</template>
