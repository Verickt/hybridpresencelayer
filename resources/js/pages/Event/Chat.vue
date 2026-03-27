<script setup lang="ts">
import { Head, useHttp } from '@inertiajs/vue3';
import { ArrowLeft, Send } from 'lucide-vue-next';
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { index as fetchMessages, store as sendMessage } from '@/routes/connection/messages';

type Message = {
    id: number;
    sender_id: number;
    sender_name: string;
    body: string;
    created_at: string;
};

const props = defineProps<{
    event: { id: number; name: string; slug: string };
    connection: { id: number };
    peer: { id: number; name: string; company: string };
}>();

const messages = ref<Message[]>([]);
const messagesContainer = ref<HTMLDivElement>();
const isTyping = ref(false);

const chatRequest = useHttp();
const messageForm = useHttp<{ body: string }>({ body: '' });

const charCount = ref(0);
const maxChars = 500;

watch(
    () => messageForm.body,
    (val) => {
        charCount.value = val?.length ?? 0;
    },
);

async function loadMessages() {
    try {
        const response = (await chatRequest.submit(
            fetchMessages(props.connection.id),
        )) as { data: Message[] };

        messages.value = response.data;
        await nextTick();
        scrollToBottom();
    } catch {
        // silently fail
    }
}

async function handleSend() {
    if (!messageForm.body.trim() || charCount.value > maxChars) {
        return;
    }

    try {
        await messageForm.submit(sendMessage(props.connection.id));

        messageForm.reset();
        await loadMessages();
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

function formatDate(iso: string): string {
    return new Intl.DateTimeFormat('en-GB', {
        day: 'numeric',
        month: 'short',
    }).format(new Date(iso));
}

// Echo listener for real-time messages
let echoChannel: unknown = null;

onMounted(() => {
    loadMessages();

    // Listen for new messages via Echo
    if (typeof window !== 'undefined' && (window as any).Echo) {
        echoChannel = (window as any).Echo.private(`connection.${props.connection.id}.chat`)
            .listen('NewMessage', (e: Message) => {
                messages.value.push(e);
                nextTick(() => scrollToBottom());
            })
            .listenForWhisper('typing', () => {
                isTyping.value = true;
                setTimeout(() => {
                    isTyping.value = false;
                }, 2000);
            });
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined' && (window as any).Echo && echoChannel) {
        (window as any).Echo.leave(`connection.${props.connection.id}.chat`);
    }
});

function handleInput() {
    if (echoChannel && typeof (echoChannel as any).whisper === 'function') {
        (echoChannel as any).whisper('typing', {});
    }
}
</script>

<template>
    <div class="flex h-dvh flex-col bg-background">
        <Head :title="`Chat with ${peer.name}`" />

        <!-- Header -->
        <div class="flex items-center gap-3 border-b border-border/70 px-4 py-3">
            <a
                :href="`/event/${event.slug}/connections`"
                class="rounded-lg p-1.5 text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
            >
                <ArrowLeft class="size-5" />
            </a>
            <div class="min-w-0 flex-1">
                <h1 class="truncate text-sm font-semibold">{{ peer.name }}</h1>
                <p v-if="peer.company" class="truncate text-xs text-muted-foreground">
                    {{ peer.company }}
                </p>
            </div>
        </div>

        <!-- Messages -->
        <div
            ref="messagesContainer"
            class="flex-1 space-y-3 overflow-y-auto p-4"
        >
            <div v-if="chatRequest.processing" class="flex items-center justify-center py-8">
                <div class="size-5 animate-spin rounded-full border-2 border-primary border-t-transparent" />
            </div>

            <div
                v-if="!chatRequest.processing && messages.length === 0"
                class="py-12 text-center text-sm text-muted-foreground"
            >
                No messages yet. Say hello!
            </div>

            <div
                v-for="msg in messages"
                :key="msg.id"
                class="space-y-0.5"
            >
                <div class="flex items-baseline gap-2">
                    <span class="text-xs font-medium">{{ msg.sender_name }}</span>
                    <span class="text-[10px] text-muted-foreground">
                        {{ formatDate(msg.created_at) }} {{ formatTime(msg.created_at) }}
                    </span>
                </div>
                <p class="text-sm">{{ msg.body }}</p>
            </div>

            <!-- Typing indicator -->
            <div v-if="isTyping" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                <span class="flex gap-0.5">
                    <span class="size-1.5 animate-bounce rounded-full bg-muted-foreground [animation-delay:0ms]" />
                    <span class="size-1.5 animate-bounce rounded-full bg-muted-foreground [animation-delay:150ms]" />
                    <span class="size-1.5 animate-bounce rounded-full bg-muted-foreground [animation-delay:300ms]" />
                </span>
                {{ peer.name }} is typing...
            </div>
        </div>

        <!-- Input -->
        <div class="border-t border-border/70 p-3">
            <div class="flex gap-2">
                <div class="relative flex-1">
                    <input
                        v-model="messageForm.body"
                        type="text"
                        :maxlength="maxChars"
                        placeholder="Type a message..."
                        class="w-full rounded-xl border border-input bg-background px-3 py-2 pr-12 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        @keydown.enter="handleSend"
                        @input="handleInput"
                    />
                    <span
                        v-if="charCount > 400"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px]"
                        :class="charCount >= maxChars ? 'text-destructive' : 'text-muted-foreground'"
                    >
                        {{ charCount }}/{{ maxChars }}
                    </span>
                </div>
                <Button
                    size="icon"
                    class="shrink-0"
                    :disabled="messageForm.processing || !messageForm.body?.trim()"
                    @click="handleSend"
                >
                    <Send class="size-4" />
                </Button>
            </div>
        </div>
    </div>
</template>
