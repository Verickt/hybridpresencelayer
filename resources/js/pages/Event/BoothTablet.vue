<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Copy,
    ExternalLink,
    MessageSquareText,
    MicVocal,
    Pin,
    Send,
    TabletSmartphone,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { show as showBooth } from '@/routes/event/booths';
import { end, start } from '@/routes/event/booths/demos';
import { answer, pin as pinThread } from '@/routes/event/booths/threads';
import { store as storeReply } from '@/routes/event/booths/threads/replies';

type BoothReply = {
    id: number;
    body: string;
    is_staff_answer: boolean;
    created_at: string | null;
    user: {
        id: number;
        name: string;
    };
};

type BoothThread = {
    id: number;
    kind: 'question' | 'demo_prompt';
    body: string;
    is_answered: boolean;
    is_pinned: boolean;
    follow_up_requested_at: string | null;
    last_activity_at: string | null;
    votes_count: number;
    user: {
        id: number;
        name: string;
    };
    replies: BoothReply[];
};

const props = defineProps<{
    event: { id: number; name: string; slug: string };
    booth: {
        id: number;
        name: string;
        company: string;
        description: string;
    };
    active_demo: {
        id: number;
        title: string;
        status: string;
        starts_at: string | null;
        prompt_thread: BoothThread | null;
    } | null;
    threads: BoothThread[];
    qr: {
        payload: string;
        svg: string;
        expires_at: string;
        booth_url: string;
    };
}>();

const demoForm = useForm({
    title: '',
});

const replyForm = useForm({
    body: '',
});

const replyingThreadId = ref<number | null>(null);
const copyMessage = ref<string | null>(null);
const copyError = ref<string | null>(null);

const unansweredThreads = computed(() =>
    props.threads.filter((thread) => !thread.is_answered),
);

const answeredThreads = computed(() =>
    props.threads.filter((thread) => thread.is_answered),
);

function formatTimestamp(timestamp: string | null): string {
    if (!timestamp) {
        return 'Gerade eben';
    }

    return new Intl.DateTimeFormat([], {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(timestamp));
}

async function copyToClipboard(value: string, label: string): Promise<void> {
    copyMessage.value = null;
    copyError.value = null;

    if (!navigator?.clipboard) {
        copyError.value = 'Zwischenablage-Zugriff ist in diesem Browser nicht verfügbar.';

        return;
    }

    try {
        await navigator.clipboard.writeText(value);
        copyMessage.value = `${label} kopiert.`;
    } catch {
        copyError.value = `${label} konnte nicht kopiert werden.`;
    }
}

function startDemo(): void {
    demoForm.post(
        start.url({
            event: props.event.slug,
            booth: props.booth.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => demoForm.reset(),
        },
    );
}

function endDemo(demoId: number): void {
    router.patch(
        end.url({
            event: props.event.slug,
            booth: props.booth.id,
            demo: demoId,
        }),
        {},
        {
            preserveScroll: true,
        },
    );
}

function startReply(threadId: number): void {
    replyingThreadId.value = threadId;
    replyForm.reset();
    replyForm.clearErrors();
}

function submitReply(threadId: number): void {
    replyForm.post(
        storeReply.url({
            event: props.event.slug,
            booth: props.booth.id,
            thread: threadId,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                replyForm.reset();
                replyingThreadId.value = null;
            },
        },
    );
}

function markAnswered(threadId: number): void {
    router.patch(
        answer.url({
            event: props.event.slug,
            booth: props.booth.id,
            thread: threadId,
        }),
        {},
        {
            preserveScroll: true,
        },
    );
}

function togglePin(threadId: number): void {
    router.patch(
        pinThread.url({
            event: props.event.slug,
            booth: props.booth.id,
            thread: threadId,
        }),
        {},
        {
            preserveScroll: true,
        },
    );
}

function refreshBoard(): void {
    router.reload({
        only: ['active_demo', 'threads'],
    });
}

onMounted(() => {
    if (!window.Echo) {
        return;
    }

    window.Echo.private(`booth.${props.booth.id}`)
        .listen('BoothThreadPosted', refreshBoard)
        .listen('BoothThreadReplyPosted', refreshBoard)
        .listen('BoothThreadVoted', refreshBoard)
        .listen('BoothDemoStarted', refreshBoard)
        .listen('BoothDemoEnded', refreshBoard);
});

onUnmounted(() => {
    window.Echo?.leaveChannel(`private-booth.${props.booth.id}`);
});
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
        <Head :title="`${booth.name} Tablet`" />

        <Card
            class="border-border/70 bg-gradient-to-br from-primary/8 via-card to-card py-0 shadow-sm"
        >
            <CardContent class="space-y-4 p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        :title="`${booth.name} Tablet-Konsole`"
                        description="Verwalten Sie das Stand-Board direkt vom Stand: Demos starten, QR-Code anzeigen und Fragen an einem Ort beantworten."
                    />

                    <Button as-child variant="outline">
                        <Link
                            :href="
                                showBooth({
                                    event: event.slug,
                                    booth: booth.id,
                                })
                            "
                        >
                            Zurück zur Stand-Seite
                        </Link>
                    </Button>
                </div>

                <div class="grid gap-3 text-sm text-muted-foreground md:grid-cols-3">
                    <div class="flex items-center gap-2">
                        <TabletSmartphone class="size-4" />
                        Nur für Stand-Mitarbeiter
                    </div>
                    <div class="flex items-center gap-2">
                        <MicVocal class="size-4" />
                        {{ active_demo ? 'Live-Demo läuft' : 'Demo inaktiv' }}
                    </div>
                    <div class="flex items-center gap-2">
                        <ExternalLink class="size-4" />
                        QR läuft ab {{ formatTimestamp(qr.expires_at) }}
                    </div>
                </div>
            </CardContent>
        </Card>

        <Alert v-if="copyError" variant="destructive">
            <AlertTitle>Kopieren fehlgeschlagen</AlertTitle>
            <AlertDescription>{{ copyError }}</AlertDescription>
        </Alert>

        <Alert v-else-if="copyMessage">
            <AlertTitle>Kopiert</AlertTitle>
            <AlertDescription>{{ copyMessage }}</AlertDescription>
        </Alert>

        <div class="grid gap-6 xl:grid-cols-[0.88fr_1.12fr]">
            <div class="space-y-6">
                <Card class="border-border/70 py-0 shadow-sm">
                    <CardContent class="flex flex-col items-center gap-4 p-6">
                        <Badge
                            class="rounded-full px-2.5 py-1 text-[11px] font-semibold tracking-[0.12em] uppercase"
                        >
                            Stand-QR
                        </Badge>

                        <div
                            class="flex w-full justify-center rounded-3xl border border-border/70 bg-white p-4 shadow-xs"
                        >
                            <div class="h-80 w-80 max-w-full" v-html="qr.svg" />
                        </div>

                        <p class="text-center text-sm text-muted-foreground">
                            Vor-Ort-Teilnehmer scannen diesen Code in der App, um direkt eingecheckt auf dem Stand-Board zu landen.
                        </p>

                        <div class="grid w-full gap-3">
                            <Button
                                variant="outline"
                                class="justify-start"
                                @click="copyToClipboard(qr.payload, 'Stand-QR-Link')"
                            >
                                <Copy class="size-4" />
                                Stand-QR-Payload kopieren
                            </Button>

                            <Button
                                variant="outline"
                                class="justify-start"
                                @click="copyToClipboard(qr.booth_url, 'Stand-Seitenlink')"
                            >
                                <Copy class="size-4" />
                                Stand-Seitenlink kopieren
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-border/70 py-0 shadow-sm">
                    <CardContent class="space-y-4 p-6">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <MessageSquareText class="size-4 text-muted-foreground" />
                                <h2 class="text-lg font-semibold">Live-Demo-Steuerung</h2>
                            </div>
                            <p class="text-sm text-muted-foreground">
                                Starten Sie eine Stand-Demo, wenn Mitarbeiter beginnen zu sprechen, oder beenden Sie sie, wenn der Stand zum asynchronen Q&A zurückkehrt.
                            </p>
                        </div>

                        <div
                            v-if="active_demo"
                            class="space-y-3 rounded-2xl border border-primary/15 bg-primary/5 p-4"
                        >
                            <div class="space-y-1">
                                <p class="font-medium">{{ active_demo.title }}</p>
                                <p class="text-sm text-muted-foreground">
                                    Gestartet {{ formatTimestamp(active_demo.starts_at) }}
                                </p>
                            </div>

                            <div
                                v-if="active_demo.prompt_thread"
                                class="rounded-2xl border border-border/70 bg-background/90 p-4"
                            >
                                <div class="flex items-center gap-2 text-xs font-semibold tracking-[0.14em] uppercase text-primary">
                                    <MicVocal class="size-3.5" />
                                    Live-Prompt
                                </div>
                                <p class="mt-2 text-sm font-medium">
                                    {{ active_demo.prompt_thread.body }}
                                </p>
                            </div>

                            <Button class="rounded-full" variant="outline" @click="endDemo(active_demo.id)">
                                Demo beenden
                            </Button>
                        </div>

                        <form
                            v-else
                            class="flex flex-col gap-3 md:flex-row"
                            @submit.prevent="startDemo"
                        >
                            <Input
                                v-model="demoForm.title"
                                placeholder="Optionaler Live-Demo-Titel"
                                class="flex-1"
                            />
                            <Button type="submit" class="rounded-full" :disabled="demoForm.processing">
                                Demo starten
                            </Button>
                        </form>

                        <InputError :message="demoForm.errors.title" />
                    </CardContent>
                </Card>
            </div>

            <Card class="border-border/70 py-0 shadow-sm">
                <CardContent class="space-y-5 p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="space-y-1">
                            <h2 class="text-lg font-semibold">Fragen-Warteschlange</h2>
                            <p class="text-sm text-muted-foreground">
                                Unbeantwortete Fragen bleiben oben, damit Stand-Mitarbeiter sie vom Tablet aus abarbeiten können.
                            </p>
                        </div>

                        <Badge variant="outline" class="rounded-full px-2.5 py-1 text-[11px]">
                            {{ unansweredThreads.length }} wartend
                        </Badge>
                    </div>

                    <div
                        v-if="!threads.length"
                        class="rounded-2xl border border-dashed border-border/70 bg-muted/30 p-6 text-sm text-muted-foreground"
                    >
                        Noch keine Stand-Fragen. Lassen Sie den QR-Code offen und die Warteschlange füllt sich, wenn Besucher beitreten.
                    </div>

                    <div v-else class="space-y-6">
                        <section v-if="unansweredThreads.length" class="space-y-3">
                            <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-muted-foreground">
                                Wartet auf Antwort
                            </h3>

                            <article
                                v-for="thread in unansweredThreads"
                                :key="thread.id"
                                class="space-y-4 rounded-3xl border border-border/70 bg-background/90 p-5"
                            >
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="space-y-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <Badge
                                                v-if="thread.is_pinned"
                                                variant="outline"
                                                class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase"
                                            >
                                                <Pin class="mr-1 size-3" />
                                                Angeheftet
                                            </Badge>
                                            <span class="text-xs text-muted-foreground">
                                                {{ thread.user.name }} · {{ formatTimestamp(thread.last_activity_at) }}
                                            </span>
                                        </div>

                                        <p class="text-base font-medium text-foreground">
                                            {{ thread.body }}
                                        </p>
                                    </div>

                                    <Badge variant="secondary" class="rounded-full">
                                        {{ thread.votes_count }} Stimmen
                                    </Badge>
                                </div>

                                <div
                                    v-if="thread.replies.length"
                                    class="space-y-2 rounded-2xl border border-border/70 bg-muted/35 p-4"
                                >
                                    <div
                                        v-for="reply in thread.replies"
                                        :key="reply.id"
                                        class="space-y-1 text-sm"
                                    >
                                        <p class="font-medium">
                                            {{ reply.user.name }}
                                            <span
                                                v-if="reply.is_staff_answer"
                                                class="text-primary"
                                            >
                                                · Mitarbeiter
                                            </span>
                                        </p>
                                        <p class="text-muted-foreground">{{ reply.body }}</p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <Button variant="outline" class="rounded-full" @click="startReply(thread.id)">
                                        <Send class="size-4" />
                                        Antworten
                                    </Button>
                                    <Button variant="outline" class="rounded-full" @click="markAnswered(thread.id)">
                                        <CheckCircle2 class="size-4" />
                                        Als beantwortet markieren
                                    </Button>
                                    <Button variant="outline" class="rounded-full" @click="togglePin(thread.id)">
                                        <Pin class="size-4" />
                                        {{ thread.is_pinned ? 'Lösen' : 'Anheften' }}
                                    </Button>
                                </div>

                                <form
                                    v-if="replyingThreadId === thread.id"
                                    class="space-y-3 rounded-2xl border border-border/70 bg-muted/35 p-4"
                                    @submit.prevent="submitReply(thread.id)"
                                >
                                    <Input
                                        v-model="replyForm.body"
                                        placeholder="Antwort vom Stand-Mitarbeiter"
                                    />
                                    <div class="flex flex-wrap gap-2">
                                        <Button type="submit" class="rounded-full" :disabled="replyForm.processing">
                                            Antwort senden
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            class="rounded-full"
                                            @click="replyingThreadId = null"
                                        >
                                            Abbrechen
                                        </Button>
                                    </div>
                                    <InputError :message="replyForm.errors.body" />
                                </form>
                            </article>
                        </section>

                        <section v-if="answeredThreads.length" class="space-y-3">
                            <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-muted-foreground">
                                Kürzlich beantwortet
                            </h3>

                            <article
                                v-for="thread in answeredThreads"
                                :key="thread.id"
                                class="space-y-3 rounded-3xl border border-border/70 bg-muted/25 p-5"
                            >
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <Badge class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase">
                                                Beantwortet
                                            </Badge>
                                            <Badge
                                                v-if="thread.is_pinned"
                                                variant="outline"
                                                class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase"
                                            >
                                                <Pin class="mr-1 size-3" />
                                                Angeheftet
                                            </Badge>
                                        </div>
                                        <p class="font-medium">{{ thread.body }}</p>
                                    </div>

                                    <span class="text-xs text-muted-foreground">
                                        {{ formatTimestamp(thread.last_activity_at) }}
                                    </span>
                                </div>

                                <div
                                    v-if="thread.replies.length"
                                    class="rounded-2xl border border-border/70 bg-background/80 p-4 text-sm text-muted-foreground"
                                >
                                    {{ thread.replies[thread.replies.length - 1]?.body }}
                                </div>
                            </article>
                        </section>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
