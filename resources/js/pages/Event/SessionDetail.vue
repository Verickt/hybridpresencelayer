<script setup lang="ts">
import { Head, router, useHttp } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { getInitials } from '@/composables/useInitials';
import { checkin, checkout } from '@/routes/event/sessions';
import { store as storeQuestion, vote as voteQuestion } from '@/routes/event/sessions/questions';
import { store as storeReaction } from '@/routes/event/sessions/reactions';

type Participant = {
    id: number;
    name: string;
    participant_type: string | null;
};

type QuestionReply = {
    id: number;
    body: string;
    user: { id: number; name: string };
    votes_count: number;
    viewer_has_voted: boolean;
    is_speaker: boolean;
    is_organizer: boolean;
    created_at: string;
};

type SessionQuestion = {
    id: number;
    body: string;
    user: { id: number; name: string };
    votes_count: number;
    viewer_has_voted: boolean;
    is_answered: boolean;
    is_pinned: boolean;
    is_hidden: boolean;
    answered_by: number | null;
    replies: QuestionReply[];
};

type ReactionType = 'lightbulb' | 'clap' | 'question' | 'fire' | 'think';

const props = defineProps<{
    event: { id: number; name: string; slug: string };
    session: {
        id: number;
        title: string;
        description: string;
        speaker: string;
        room: string;
        starts_at: string;
        ends_at: string;
        is_live: boolean;
        is_joinable: boolean;
        qa_enabled: boolean;
        reactions_enabled: boolean;
        can_interact: boolean;
        speaker_user_id: number | null;
    };
    viewer: {
        is_organizer: boolean;
        is_speaker: boolean;
        is_moderator: boolean;
        participant_type: string | null;
        is_checked_in: boolean;
        can_join: boolean;
        can_interact: boolean;
    };
    participants: Participant[];
    questions: SessionQuestion[];
}>();

const activeTab = ref<'people' | 'qa'>('people');
const joinRequest = useHttp();
const reactionRequest = useHttp<{ type: ReactionType }>({ type: 'clap' });
const questionRequest = useHttp<{ body: string }>({ body: '' });
const voteRequest = useHttp();
const replyRequest = useHttp<{ body: string }>({ body: '' });
const expandedQuestionId = ref<number | null>(null);

const physicalParticipants = computed(() => props.participants.filter((p) => p.participant_type === 'physical'));
const remoteParticipants = computed(() => props.participants.filter((p) => p.participant_type !== 'physical'));

const reactionEmojis: Array<{ type: ReactionType; emoji: string }> = [
    { type: 'lightbulb', emoji: '💡' },
    { type: 'clap', emoji: '👏' },
    { type: 'question', emoji: '❓' },
    { type: 'fire', emoji: '🔥' },
    { type: 'think', emoji: '🤔' },
];

// Reaction cluster badge
const WINDOW_SECONDS = 30;
const reactionWindows = ref<Map<number, Set<number>>>(new Map());
const clusterCount = ref(0);
const showClusterBadge = ref(false);
let clusterDismissTimer: ReturnType<typeof setTimeout> | null = null;

function getCurrentWindow(): number {
    const sessionStart = new Date(props.session.starts_at).getTime();
    return Math.floor((Date.now() - sessionStart) / (WINDOW_SECONDS * 1000));
}

function trackReaction(userId: number) {
    const window = getCurrentWindow();
    if (!reactionWindows.value.has(window)) {
        reactionWindows.value.set(window, new Set());
    }
    reactionWindows.value.get(window)!.add(userId);

    const othersInWindow = reactionWindows.value.get(window)!.size - 1;
    if (othersInWindow > 0) {
        clusterCount.value = othersInWindow;
        showClusterBadge.value = true;
        if (clusterDismissTimer) clearTimeout(clusterDismissTimer);
        clusterDismissTimer = setTimeout(() => { showClusterBadge.value = false; }, 15000);
    }
}

function formatTimeRange(startsAt: string, endsAt: string): string {
    const fmt = new Intl.DateTimeFormat([], { hour: 'numeric', minute: '2-digit' });
    return `${fmt.format(new Date(startsAt))}–${fmt.format(new Date(endsAt))}`;
}

function formatTime(dateStr: string): string {
    return new Intl.DateTimeFormat([], { hour: 'numeric', minute: '2-digit' }).format(new Date(dateStr));
}

async function refresh() {
    await new Promise<void>((resolve) => {
        router.reload({ only: ['session', 'viewer', 'participants', 'questions'], preserveScroll: true, preserveState: true, onFinish: () => resolve() });
    });
}

async function handleJoin() {
    try {
        await joinRequest.submit(checkin({ event: props.event.slug, session: props.session.id }));
        await refresh();
    } catch { /* */ }
}

async function handleLeave() {
    try {
        await joinRequest.submit(checkout({ event: props.event.slug, session: props.session.id }));
        await refresh();
    } catch { /* */ }
}

async function handleReaction(type: ReactionType) {
    reactionRequest.type = type;
    try {
        await reactionRequest.submit(storeReaction({ event: props.event.slug, session: props.session.id }));
    } catch { /* */ }
}

async function handleQuestionSubmit() {
    if (!questionRequest.body.trim()) return;
    try {
        await questionRequest.submit(storeQuestion({ event: props.event.slug, session: props.session.id }));
        questionRequest.reset();
        await refresh();
    } catch { /* */ }
}

async function handleVote(questionId: number) {
    try {
        await voteRequest.submit(voteQuestion({ event: props.event.slug, session: props.session.id, question: questionId }));
        await refresh();
    } catch { /* */ }
}

async function handleReplySubmit(questionId: number) {
    if (!replyRequest.body.trim()) return;
    try {
        await router.post(
            `/event/${props.event.slug}/sessions/${props.session.id}/questions/${questionId}/replies`,
            { body: replyRequest.body },
            { preserveScroll: true, onSuccess: () => { replyRequest.reset(); refresh(); } },
        );
    } catch { /* */ }
}

async function handleReplyVote(questionId: number, replyId: number) {
    try {
        await router.post(
            `/event/${props.event.slug}/sessions/${props.session.id}/questions/${questionId}/replies/${replyId}/vote`,
            {},
            { preserveScroll: true, onSuccess: () => refresh() },
        );
    } catch { /* */ }
}

async function handlePin(questionId: number) {
    await router.post(`/event/${props.event.slug}/sessions/${props.session.id}/questions/${questionId}/pin`, {}, { preserveScroll: true, onSuccess: () => refresh() });
}

async function handleHide(questionId: number) {
    await router.post(`/event/${props.event.slug}/sessions/${props.session.id}/questions/${questionId}/hide`, {}, { preserveScroll: true, onSuccess: () => refresh() });
}

async function handleAnswer(questionId: number) {
    await router.post(`/event/${props.event.slug}/sessions/${props.session.id}/questions/${questionId}/answer`, {}, { preserveScroll: true, onSuccess: () => refresh() });
}

onMounted(() => {
    if (typeof window !== 'undefined' && (window as any).Echo) {
        const echo = (window as any).Echo;
        echo.private(`session.${props.session.id}`)
            .listen('SessionReactionSent', (e: { type: string; user_id: number }) => {
                trackReaction(e.user_id);
            })
            .listen('SessionQuestionPosted', () => {
                refresh();
            })
            .listen('SessionQuestionReplyPosted', () => {
                refresh();
            })
            .listen('SessionQuestionPinned', () => {
                refresh();
            })
            .listen('SessionEnded', () => {
                router.visit(`/event/${props.event.slug}/sessions/${props.session.id}/connections`);
            });
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined' && (window as any).Echo) {
        (window as any).Echo.leave(`session.${props.session.id}`);
    }
    if (clusterDismissTimer) clearTimeout(clusterDismissTimer);
});
</script>

<template>
    <div class="flex h-full flex-1 flex-col pb-20">
        <Head :title="session.title" />

        <!-- Header -->
        <div class="p-4">
            <div class="flex items-center gap-2">
                <button class="text-neutral-400" @click="router.visit(`/event/${event.slug}/sessions`)">
                    <ArrowLeft class="size-5" />
                </button>
                <span
                    v-if="session.is_live"
                    class="inline-flex items-center gap-1 rounded-full bg-red-500 px-2 py-0.5 text-[11px] font-semibold text-white"
                >
                    <span class="size-1.5 animate-pulse rounded-full bg-white" />
                    LIVE
                </span>
            </div>

            <h1 class="mt-3 text-xl font-bold text-neutral-900">{{ session.title }}</h1>
            <p class="mt-1 text-sm text-neutral-500">
                {{ session.speaker }} · {{ formatTimeRange(session.starts_at, session.ends_at) }} · {{ session.room }}
            </p>
            <div class="mt-1 flex items-center gap-3 text-xs text-neutral-500">
                <span>📍 {{ physicalParticipants.length }} vor Ort</span>
                <span>🌐 {{ remoteParticipants.length }} remote</span>
            </div>

            <!-- Moderate link for organizers/moderators -->
            <button
                v-if="viewer.is_moderator"
                class="mt-2 text-xs font-medium text-indigo-600 hover:underline"
                @click="router.visit(`/event/${event.slug}/sessions/${session.id}/moderate`)"
            >
                Session moderieren →
            </button>

            <!-- Join/Leave -->
            <div class="mt-3">
                <button
                    v-if="viewer.can_join && !viewer.is_checked_in"
                    class="rounded-full bg-indigo-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700"
                    :disabled="joinRequest.processing"
                    @click="handleJoin"
                >
                    Einchecken
                </button>
                <button
                    v-else-if="viewer.is_checked_in"
                    class="rounded-full border border-neutral-200 px-5 py-2 text-sm font-medium text-neutral-600 transition hover:bg-neutral-50"
                    :disabled="joinRequest.processing"
                    @click="handleLeave"
                >
                    Session verlassen
                </button>
            </div>
        </div>

        <!-- Tab switcher -->
        <div class="flex border-b border-neutral-100 px-4">
            <button
                class="relative px-4 py-2.5 text-sm font-medium transition"
                :class="activeTab === 'people' ? 'text-indigo-600' : 'text-neutral-400'"
                @click="activeTab = 'people'"
            >
                Personen
                <span
                    v-if="activeTab === 'people'"
                    class="absolute bottom-0 left-0 right-0 h-0.5 rounded-full bg-indigo-600"
                />
            </button>
            <button
                class="relative px-4 py-2.5 text-sm font-medium transition"
                :class="activeTab === 'qa' ? 'text-indigo-600' : 'text-neutral-400'"
                @click="activeTab = 'qa'"
            >
                Q&A ({{ questions.length }})
                <span
                    v-if="activeTab === 'qa'"
                    class="absolute bottom-0 left-0 right-0 h-0.5 rounded-full bg-indigo-600"
                />
            </button>
        </div>

        <!-- Tab content -->
        <div class="flex-1 overflow-y-auto p-4">
            <!-- People tab -->
            <div v-if="activeTab === 'people'">
                <!-- Physical section -->
                <div v-if="physicalParticipants.length > 0">
                    <p class="mb-2 text-[11px] font-semibold tracking-wider text-neutral-400 uppercase">
                        📍 Physical · {{ physicalParticipants.length }}
                    </p>
                    <div class="space-y-1">
                        <div
                            v-for="p in physicalParticipants"
                            :key="p.id"
                            class="flex items-center gap-3 rounded-xl py-2"
                        >
                            <div class="flex size-9 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700">
                                {{ getInitials(p.name) }}
                            </div>
                            <span class="text-sm font-medium text-neutral-900">{{ p.name }}</span>
                            <span class="ml-auto text-lg">👋</span>
                        </div>
                    </div>
                </div>

                <!-- Remote section -->
                <div v-if="remoteParticipants.length > 0" :class="{ 'mt-6': physicalParticipants.length > 0 }">
                    <p class="mb-2 text-[11px] font-semibold tracking-wider text-neutral-400 uppercase">
                        🌐 Remote · {{ remoteParticipants.length }}
                    </p>
                    <div class="space-y-1">
                        <div
                            v-for="p in remoteParticipants"
                            :key="p.id"
                            class="flex items-center gap-3 rounded-xl py-2"
                        >
                            <div class="flex size-9 items-center justify-center rounded-full bg-emerald-100 text-xs font-semibold text-emerald-700">
                                {{ getInitials(p.name) }}
                            </div>
                            <span class="text-sm font-medium text-neutral-900">{{ p.name }}</span>
                            <span class="ml-auto text-lg">👋</span>
                        </div>
                    </div>
                </div>

                <p v-if="participants.length === 0" class="py-8 text-center text-sm text-neutral-400">
                    Noch keine aktiven Teilnehmer.
                </p>
            </div>

            <!-- Q&A tab -->
            <div v-else-if="activeTab === 'qa'">
                <!-- Ask question form -->
                <div v-if="session.qa_enabled" class="mb-4">
                    <label class="text-sm font-medium text-neutral-700">Fragen Sie den Raum</label>
                    <textarea
                        v-model="questionRequest.body"
                        rows="3"
                        class="mt-1 w-full rounded-xl border border-neutral-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"
                        placeholder="Stellen Sie Ihre Frage an den Referenten oder Moderator."
                        :disabled="!viewer.can_interact || questionRequest.processing"
                    />
                    <div class="mt-2 flex justify-end">
                        <button
                            class="rounded-full bg-neutral-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-neutral-800 disabled:opacity-40"
                            :disabled="!viewer.can_interact || questionRequest.processing || !questionRequest.body.trim()"
                            @click="handleQuestionSubmit"
                        >
                            Frage absenden
                        </button>
                    </div>
                </div>

                <!-- Question list -->
                <div v-if="questions.length > 0" class="space-y-3">
                    <div
                        v-for="question in questions"
                        :key="question.id"
                        class="rounded-xl border border-neutral-100 bg-white p-4"
                    >
                        <!-- Status badges -->
                        <div v-if="question.is_pinned || question.is_answered || question.is_hidden" class="mb-2 flex flex-wrap gap-1.5">
                            <span v-if="question.is_pinned" class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700">
                                📌 Angepinnt
                            </span>
                            <span v-if="question.is_answered" class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2 py-0.5 text-[11px] font-medium text-green-700">
                                ✅ Beantwortet
                            </span>
                            <span v-if="question.is_hidden" class="inline-flex items-center gap-1 rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-medium text-neutral-500">
                                Versteckt
                            </span>
                        </div>

                        <p class="text-sm font-medium text-neutral-900">{{ question.body }}</p>
                        <div class="mt-2 flex items-center justify-between">
                            <p class="text-xs text-neutral-500">
                                {{ question.user.name }} · {{ question.votes_count }} Stimme{{ question.votes_count !== 1 ? 'n' : '' }}
                            </p>
                            <button
                                class="rounded-full border px-3 py-1 text-xs font-medium transition"
                                :class="question.viewer_has_voted
                                    ? 'border-indigo-200 bg-indigo-50 text-indigo-600'
                                    : 'border-neutral-200 text-neutral-600 hover:bg-neutral-50'"
                                :disabled="!viewer.can_interact || question.viewer_has_voted || voteRequest.processing"
                                :dusk="`session-question-vote-button-${question.id}`"
                                @click="handleVote(question.id)"
                            >
                                {{ question.viewer_has_voted ? 'Abgestimmt' : 'Abstimmen' }}
                            </button>
                        </div>

                        <!-- Moderator actions -->
                        <div v-if="viewer.is_moderator" class="mt-3 flex flex-wrap gap-2 border-t border-neutral-50 pt-3">
                            <button
                                class="rounded-full border border-neutral-200 px-3 py-1 text-xs font-medium text-neutral-600 transition hover:bg-neutral-50"
                                @click="handlePin(question.id)"
                            >
                                {{ question.is_pinned ? 'Lösen' : 'Anpinnen' }}
                            </button>
                            <button
                                class="rounded-full border border-neutral-200 px-3 py-1 text-xs font-medium text-neutral-600 transition hover:bg-neutral-50"
                                @click="handleHide(question.id)"
                            >
                                {{ question.is_hidden ? 'Anzeigen' : 'Verbergen' }}
                            </button>
                            <button
                                class="rounded-full border border-neutral-200 px-3 py-1 text-xs font-medium text-neutral-600 transition hover:bg-neutral-50"
                                @click="handleAnswer(question.id)"
                            >
                                {{ question.is_answered ? 'Markierung aufheben' : 'Als beantwortet markieren' }}
                            </button>
                        </div>

                        <!-- Replies toggle -->
                        <button
                            class="mt-3 text-xs font-medium text-indigo-600 hover:underline"
                            @click="expandedQuestionId = expandedQuestionId === question.id ? null : question.id"
                        >
                            {{ expandedQuestionId === question.id ? 'Antworten ausblenden' : `Antworten anzeigen (${question.replies?.length ?? 0})` }}
                        </button>

                        <!-- Replies section -->
                        <div v-if="expandedQuestionId === question.id" class="mt-3 space-y-3">
                            <!-- Existing replies -->
                            <div
                                v-for="reply in question.replies"
                                :key="reply.id"
                                class="rounded-lg bg-neutral-50 p-3"
                            >
                                <p class="text-sm text-neutral-800">{{ reply.body }}</p>
                                <div class="mt-1.5 flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs text-neutral-500">{{ reply.user.name }}</span>
                                        <span v-if="reply.is_speaker" class="rounded-full bg-indigo-100 px-1.5 py-0.5 text-[10px] font-semibold text-indigo-700">
                                            Sprecher
                                        </span>
                                        <span v-else-if="reply.is_organizer" class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700">
                                            Organisator
                                        </span>
                                        <span class="text-[10px] text-neutral-400">{{ formatTime(reply.created_at) }}</span>
                                    </div>
                                    <button
                                        class="rounded-full border px-2.5 py-0.5 text-[11px] font-medium transition"
                                        :class="reply.viewer_has_voted
                                            ? 'border-indigo-200 bg-indigo-50 text-indigo-600'
                                            : 'border-neutral-200 text-neutral-600 hover:bg-neutral-50'"
                                        :disabled="!viewer.can_interact || reply.viewer_has_voted"
                                        @click="handleReplyVote(question.id, reply.id)"
                                    >
                                        {{ reply.votes_count }} · {{ reply.viewer_has_voted ? 'Abgestimmt' : 'Abstimmen' }}
                                    </button>
                                </div>
                            </div>

                            <!-- Reply form -->
                            <div v-if="viewer.can_interact" class="mt-2">
                                <textarea
                                    v-model="replyRequest.body"
                                    rows="2"
                                    class="w-full rounded-xl border border-neutral-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"
                                    placeholder="Antwort schreiben …"
                                    :disabled="replyRequest.processing"
                                />
                                <div class="mt-1.5 flex justify-end">
                                    <button
                                        class="rounded-full bg-neutral-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-neutral-800 disabled:opacity-40"
                                        :disabled="replyRequest.processing || !replyRequest.body.trim()"
                                        @click="handleReplySubmit(question.id)"
                                    >
                                        Antwort absenden
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <p v-else class="py-8 text-center text-sm text-neutral-400">
                    Noch keine Fragen. Stellen Sie die erste!
                </p>
            </div>
        </div>

        <!-- Reaction cluster badge -->
        <Transition name="fade">
            <div
                v-if="showClusterBadge && viewer.is_checked_in"
                class="fixed bottom-36 left-1/2 z-40 -translate-x-1/2 rounded-full bg-neutral-900/80 px-4 py-2 text-sm font-medium text-white shadow-lg backdrop-blur"
            >
                {{ clusterCount }} {{ clusterCount === 1 ? 'andere Person' : 'andere Personen' }} fühlt das auch
            </div>
        </Transition>

        <!-- Floating emoji reaction bar -->
        <div
            v-if="viewer.is_checked_in && session.reactions_enabled"
            class="fixed bottom-20 left-1/2 z-30 flex -translate-x-1/2 gap-1 rounded-full border border-neutral-200 bg-white px-3 py-2 shadow-lg"
        >
            <button
                v-for="r in reactionEmojis"
                :key="r.type"
                class="flex size-10 items-center justify-center rounded-full text-xl transition hover:bg-neutral-100 active:scale-110"
                :disabled="!viewer.can_interact || reactionRequest.processing"
                :dusk="`session-reaction-button-${r.type}`"
                @click="handleReaction(r.type)"
            >
                {{ r.emoji }}
            </button>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
