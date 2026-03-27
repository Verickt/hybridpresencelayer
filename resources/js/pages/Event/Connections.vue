<script setup lang="ts">
import { Head, useHttp } from '@inertiajs/vue3';
import { MessageCircle, Phone, PhoneOff, Send, X } from 'lucide-vue-next';
import { nextTick, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { start as startCall, end as endCall } from '@/routes/connection/call';
import { index as fetchMessages, store as sendMessage } from '@/routes/connection/messages';

type ConnectionItem = {
    connection_id: number;
    user: { id: number; name: string; company: string };
    context: string;
    is_cross_world: boolean;
    created_at: string;
};

type Message = {
    id: number;
    sender_id: number;
    sender_name: string;
    body: string;
    created_at: string;
};

defineProps<{
    event: { id: number; name: string; slug: string };
    connections: ConnectionItem[];
}>();

const activeChat = ref<number | null>(null);
const messages = ref<Message[]>([]);
const activeCall = ref<{ callId: number; roomId: string; expiresAt: string } | null>(null);
const messagesContainer = ref<HTMLDivElement>();

const chatRequest = useHttp();
const messageForm = useHttp<{ body: string }>({ body: '' });
const callRequest = useHttp();

async function openChat(connectionId: number) {
    activeChat.value = connectionId;
    messages.value = [];

    try {
        const response = (await chatRequest.submit(
            fetchMessages(connectionId),
        )) as { data: Message[] };

        messages.value = response.data;
        await nextTick();
        scrollToBottom();
    } catch {
        // silently fail
    }
}

function closeChat() {
    activeChat.value = null;
    messages.value = [];
    activeCall.value = null;
}

async function handleSend() {
    if (!activeChat.value || !messageForm.body.trim()) {
        return;
    }

    try {
        await messageForm.submit(sendMessage(activeChat.value));

        const response = (await chatRequest.submit(
            fetchMessages(activeChat.value),
        )) as { data: Message[] };

        messages.value = response.data;
        messageForm.reset();
        await nextTick();
        scrollToBottom();
    } catch {
        // silently fail
    }
}

async function handleStartCall() {
    if (!activeChat.value) {
        return;
    }

    try {
        const response = (await callRequest.submit(
            startCall(activeChat.value),
        )) as { call_id: number; room_id: string; expires_at: string };

        activeCall.value = {
            callId: response.call_id,
            roomId: response.room_id,
            expiresAt: response.expires_at,
        };
    } catch {
        // silently fail
    }
}

async function handleEndCall() {
    if (!activeChat.value || !activeCall.value) {
        return;
    }

    try {
        await callRequest.submit(
            endCall({ connection: activeChat.value, call: activeCall.value.callId }),
        );

        activeCall.value = null;
    } catch {
        // silently fail
    }
}

function scrollToBottom() {
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
}

function formatTime(iso: string): string {
    return new Intl.DateTimeFormat('en-GB', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    }).format(new Date(iso));
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
        <Head :title="`${event.name} - Connections`" />

        <Heading
            title="Your connections"
            :description="`People you connected with at ${event.name}.`"
        />

        <p v-if="connections.length === 0" class="text-sm text-muted-foreground">
            No connections yet. Start meeting people!
        </p>

        <!-- Chat overlay -->
        <div
            v-if="activeChat"
            class="fixed inset-0 z-50 flex flex-col bg-background md:inset-auto md:bottom-4 md:right-4 md:h-[32rem] md:w-96 md:rounded-2xl md:border md:border-border/70 md:shadow-lg"
        >
            <div class="flex items-center justify-between border-b border-border/70 p-4">
                <h3 class="font-semibold">
                    {{
                        connections.find((c) => c.connection_id === activeChat)
                            ?.user.name ?? 'Chat'
                    }}
                </h3>
                <div class="flex items-center gap-2">
                    <Button
                        v-if="!activeCall"
                        size="icon"
                        variant="ghost"
                        class="size-8"
                        :disabled="callRequest.processing"
                        @click="handleStartCall"
                    >
                        <Phone class="size-4" />
                    </Button>
                    <Button
                        v-else
                        size="icon"
                        variant="destructive"
                        class="size-8"
                        :disabled="callRequest.processing"
                        @click="handleEndCall"
                    >
                        <PhoneOff class="size-4" />
                    </Button>
                    <Button
                        size="icon"
                        variant="ghost"
                        class="size-8"
                        @click="closeChat"
                    >
                        <X class="size-4" />
                    </Button>
                </div>
            </div>

            <div
                v-if="activeCall"
                class="border-b border-border/70 bg-primary/5 px-4 py-2 text-sm"
            >
                Call active — Room: <code class="text-xs">{{ activeCall.roomId.slice(0, 8) }}...</code>
            </div>

            <div
                ref="messagesContainer"
                class="flex-1 space-y-3 overflow-y-auto p-4"
            >
                <div v-if="chatRequest.processing" class="text-center text-sm text-muted-foreground">
                    Loading...
                </div>
                <div
                    v-for="msg in messages"
                    :key="msg.id"
                    class="space-y-0.5"
                >
                    <div class="flex items-baseline gap-2">
                        <span class="text-xs font-medium">{{ msg.sender_name }}</span>
                        <span class="text-[10px] text-muted-foreground">{{ formatTime(msg.created_at) }}</span>
                    </div>
                    <p class="text-sm">{{ msg.body }}</p>
                </div>
                <div
                    v-if="!chatRequest.processing && messages.length === 0"
                    class="text-center text-sm text-muted-foreground"
                >
                    No messages yet. Say hello!
                </div>
            </div>

            <div class="border-t border-border/70 p-3">
                <div class="flex gap-2">
                    <input
                        v-model="messageForm.body"
                        type="text"
                        placeholder="Type a message..."
                        class="flex-1 rounded-xl border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        @keydown.enter="handleSend"
                    />
                    <Button
                        size="icon"
                        class="shrink-0"
                        :disabled="messageForm.processing || !messageForm.body.trim()"
                        @click="handleSend"
                    >
                        <Send class="size-4" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- Connection list -->
        <Card
            v-for="connection in connections"
            :key="connection.connection_id"
            class="shadow-sm"
        >
            <CardContent class="flex items-center justify-between gap-4 p-4">
                <div class="min-w-0">
                    <p class="truncate font-medium">{{ connection.user.name }}</p>
                    <p class="truncate text-sm text-muted-foreground">
                        {{ connection.user.company }}
                    </p>
                    <p
                        v-if="connection.context"
                        class="mt-1 text-xs text-muted-foreground"
                    >
                        {{ connection.context }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Badge
                        v-if="connection.is_cross_world"
                        variant="secondary"
                        class="shrink-0"
                    >
                        Cross-world
                    </Badge>
                    <Button
                        size="sm"
                        variant="outline"
                        class="shrink-0"
                        @click="openChat(connection.connection_id)"
                    >
                        <MessageCircle class="mr-1 size-3.5" />
                        Chat
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
