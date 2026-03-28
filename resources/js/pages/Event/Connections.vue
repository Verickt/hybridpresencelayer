<script setup lang="ts">
import { Head, router, useHttp } from '@inertiajs/vue3';
import { MessageCircle, Phone } from 'lucide-vue-next';
import { useHaptics } from '@/composables/useHaptics';
import { ping } from '@/routes/event';

const { ping: hapticPing } = useHaptics();

type ConnectionItem = {
    connection_id: number;
    user: { id: number; name: string; company: string };
    context: string;
    is_cross_world: boolean;
    created_at: string;
};

type IncomingPing = {
    ping_id: number;
    user: { id: number; name: string; company: string; role_title?: string };
    created_at: string;
};

const props = defineProps<{
    event: { id: number; name: string; slug: string };
    connections: ConnectionItem[];
    incomingPings: IncomingPing[];
}>();

const pingRequest = useHttp();

function openChat(connectionId: number) {
    router.visit(`/event/${props.event.slug}/connections/${connectionId}/chat`);
}

async function handlePingBack(userId: number) {
    hapticPing();
    try {
        await pingRequest.submit(ping({ event: props.event.slug, user: userId }));
        router.reload({ only: ['connections', 'incomingPings'] });
    } catch {
        // silently fail
    }
}

function timeAgo(iso: string): string {
    const diff = Math.floor((Date.now() - new Date(iso).getTime()) / 60000);
    if (diff < 1) return 'Gerade eben';
    if (diff < 60) return `Vor ${diff} Min.`;
    return `Vor ${Math.floor(diff / 60)} Std.`;
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
        <Head :title="`${event.name} - Connections`" />

        <div class="space-y-1">
            <h1 class="text-2xl font-bold text-neutral-900">Verbindungen</h1>
            <p class="text-sm text-neutral-500">{{ connections.length }} Personen, mit denen Sie gematcht haben</p>
        </div>

        <!-- Incoming pings section -->
        <div v-if="incomingPings.length > 0">
            <p class="mb-2 text-[11px] font-semibold tracking-wider text-indigo-600 uppercase">
                👋 {{ incomingPings.length }} {{ incomingPings.length === 1 ? 'Person hat' : 'Personen haben' }} dich gepingt
            </p>
            <div class="space-y-2">
                <div
                    v-for="p in incomingPings"
                    :key="p.ping_id"
                    class="flex items-center gap-3 rounded-xl border border-indigo-100 bg-indigo-50/50 px-4 py-3"
                >
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700">
                        {{ p.user.name.split(' ').map((n: string) => n[0]).join('') }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-neutral-900">{{ p.user.name }}</p>
                        <p class="text-xs text-neutral-500">
                            {{ p.user.role_title || p.user.company }} · {{ timeAgo(p.created_at) }}
                        </p>
                    </div>

                    <button
                        class="shrink-0 rounded-full bg-indigo-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-indigo-700"
                        :disabled="pingRequest.processing"
                        @click="handlePingBack(p.user.id)"
                    >
                        👋 Zurückpingen
                    </button>
                </div>
            </div>
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
                        @click.stop="openChat(connection.connection_id)"
                    >
                        <Phone class="size-4" />
                    </button>
                </div>
            </div>
        </div>

        <p v-if="connections.length === 0 && incomingPings.length === 0" class="py-8 text-center text-sm text-neutral-400">
            Noch keine Verbindungen. Lernen Sie neue Leute kennen!
        </p>
    </div>
</template>
