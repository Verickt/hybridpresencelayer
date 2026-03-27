<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router, useHttp } from '@inertiajs/vue3';
import {
    CalendarDays,
    MessageSquareText,
    Mic2,
    QrCode,
    Sparkles,
    Users,
} from 'lucide-vue-next';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { getInitials } from '@/composables/useInitials';
import Heading from '@/components/Heading.vue';
import {
    checkin,
    checkout,
    qrDisplay,
} from '@/routes/event/sessions';
import { store as storeQuestion, vote as voteQuestion } from '@/routes/event/sessions/questions';
import { store as storeReaction } from '@/routes/event/sessions/reactions';

type Participant = {
    id: number;
    name: string;
    participant_type: string | null;
};

type SessionQuestion = {
    id: number;
    body: string;
    user: { id: number; name: string };
    votes_count: number;
    viewer_has_voted: boolean;
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
    };
    viewer: {
        is_organizer: boolean;
        participant_type: string | null;
        is_checked_in: boolean;
        can_join: boolean;
        can_interact: boolean;
    };
    participants: Participant[];
    questions: SessionQuestion[];
}>();

const joinRequest = useHttp();
const reactionRequest = useHttp<{ type: ReactionType }>({ type: 'clap' });
const questionRequest = useHttp<{ body: string }>({ body: '' });
const voteRequest = useHttp();

const feedbackMessage = ref<string | null>(null);
const feedbackError = ref<string | null>(null);

const reactionTypes: Array<{ type: ReactionType; label: string }> = [
    { type: 'clap', label: 'Clap' },
    { type: 'lightbulb', label: 'Idea' },
    { type: 'question', label: 'Question' },
    { type: 'fire', label: 'Fire' },
    { type: 'think', label: 'Think' },
];

const joinStateMessage = computed(() => {
    if (props.viewer.is_checked_in) {
        return 'You are currently checked into this session.';
    }

    if (props.viewer.is_organizer) {
        return 'Organizer mode is read-only. Use the room QR page for in-room attendees and share the remote join link with remote attendees.';
    }

    if (! props.session.is_joinable) {
        return new Date(props.session.starts_at).getTime() > Date.now()
            ? 'Join opens 10 minutes before the scheduled start.'
            : 'The join window has closed for this session.';
    }

    if (! props.session.can_interact) {
        return 'Check in now. Reactions and Q&A unlock when the session goes live.';
    }

    return 'Join the session to react, ask questions, and vote in Q&A.';
});

const interactionHelpText = computed(() => {
    if (props.viewer.is_organizer) {
        return 'Organizer mode keeps participant controls disabled on this page.';
    }

    if (props.viewer.can_interact) {
        return 'Live controls are open. Your reactions, questions, and votes post immediately.';
    }

    if (props.viewer.is_checked_in) {
        return 'You are checked in. Interactive controls unlock while the session is live.';
    }

    return 'Interactive controls unlock after you join the session.';
});

function formatTimeRange(startsAt: string, endsAt: string): string {
    const formatter = new Intl.DateTimeFormat('en-GB', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    });

    return `${formatter.format(new Date(startsAt))} - ${formatter.format(new Date(endsAt))}`;
}

async function refreshSessionPage(): Promise<void> {
    await new Promise<void>((resolve) => {
        router.reload({
            only: ['session', 'viewer', 'participants', 'questions'],
            preserveScroll: true,
            preserveState: true,
            onFinish: () => resolve(),
        });
    });
}

function clearFeedback(): void {
    feedbackMessage.value = null;
    feedbackError.value = null;
}

async function handleJoin(): Promise<void> {
    clearFeedback();

    try {
        const response = (await joinRequest.submit(
            checkin({ event: props.event.slug, session: props.session.id }),
        )) as { message: string };

        feedbackMessage.value = response.message;
        await refreshSessionPage();
    } catch {
        feedbackError.value = 'Unable to join the session right now.';
    }
}

async function handleLeave(): Promise<void> {
    clearFeedback();

    try {
        const response = (await joinRequest.submit(
            checkout({ event: props.event.slug, session: props.session.id }),
        )) as { message: string };

        feedbackMessage.value = response.message;
        await refreshSessionPage();
    } catch {
        feedbackError.value = 'Unable to leave the session right now.';
    }
}

async function handleReaction(type: ReactionType): Promise<void> {
    clearFeedback();
    reactionRequest.clearErrors();
    reactionRequest.type = type;

    try {
        const response = (await reactionRequest.submit(
            storeReaction({
                event: props.event.slug,
                session: props.session.id,
            }),
        )) as { message: string };

        feedbackMessage.value = response.message;
    } catch {
        feedbackError.value = 'Unable to send that reaction right now.';
    }
}

async function handleQuestionSubmit(): Promise<void> {
    clearFeedback();
    questionRequest.clearErrors();

    if (! questionRequest.body.trim()) {
        questionRequest.setError('body', 'Please enter a question.');

        return;
    }

    try {
        const response = (await questionRequest.submit(
            storeQuestion({
                event: props.event.slug,
                session: props.session.id,
            }),
        )) as { message: string };

        feedbackMessage.value = response.message;
        questionRequest.reset();
        await refreshSessionPage();
    } catch {
        if (! questionRequest.errors.body) {
            feedbackError.value = 'Unable to submit that question right now.';
        }
    }
}

async function handleVote(questionId: number): Promise<void> {
    clearFeedback();

    try {
        const response = (await voteRequest.submit(
            voteQuestion({
                event: props.event.slug,
                session: props.session.id,
                question: questionId,
            }),
        )) as { message: string };

        feedbackMessage.value = response.message;
        await refreshSessionPage();
    } catch {
        feedbackError.value = 'Unable to record that vote right now.';
    }
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
        <Head :title="session.title" />

        <Card
            class="border-border/70 bg-gradient-to-br from-primary/8 via-card to-card py-0 shadow-sm"
        >
            <CardContent class="space-y-4 p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        :title="session.title"
                        :description="session.description"
                    />

                    <div class="flex flex-wrap gap-2">
                        <Badge
                            v-if="viewer.is_checked_in"
                            class="rounded-full px-2.5 py-1 text-[11px] font-semibold tracking-[0.12em] uppercase"
                        >
                            In Session
                        </Badge>
                        <Badge
                            v-if="session.is_live"
                            class="rounded-full px-2.5 py-1 text-[11px] font-semibold tracking-[0.12em] uppercase"
                        >
                            Live
                        </Badge>
                        <Badge
                            v-if="session.qa_enabled"
                            variant="secondary"
                            class="rounded-full px-2.5 py-1 text-[11px]"
                        >
                            Q&amp;A
                        </Badge>
                        <Badge
                            v-if="session.reactions_enabled"
                            variant="outline"
                            class="rounded-full px-2.5 py-1 text-[11px]"
                        >
                            Reactions on
                        </Badge>
                    </div>
                </div>

                <div
                    class="grid gap-3 text-sm text-muted-foreground md:grid-cols-3"
                >
                    <div class="flex items-center gap-2">
                        <CalendarDays class="size-4" />
                        {{
                            formatTimeRange(session.starts_at, session.ends_at)
                        }}
                    </div>
                    <div class="flex items-center gap-2">
                        <Mic2 class="size-4" />
                        {{ session.speaker || 'Speaker TBA' }}
                    </div>
                    <div class="flex items-center gap-2">
                        <Users class="size-4" />
                        {{ session.room || 'Room to be announced' }}
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card class="border-border/70 py-0 shadow-sm">
            <CardContent class="space-y-5 p-6">
                <div
                    class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between"
                >
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <Sparkles class="size-4 text-muted-foreground" />
                            <h2 class="text-lg font-semibold">Session controls</h2>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            {{ joinStateMessage }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-if="viewer.can_join && ! viewer.is_checked_in"
                            :disabled="joinRequest.processing"
                            @click="handleJoin"
                        >
                            Join session
                        </Button>

                        <Button
                            v-if="viewer.is_checked_in"
                            variant="outline"
                            :disabled="joinRequest.processing"
                            @click="handleLeave"
                        >
                            Leave session
                        </Button>

                        <Button
                            v-if="viewer.is_organizer"
                            as-child
                            variant="outline"
                        >
                            <Link
                                :href="
                                    qrDisplay({
                                        event: event.slug,
                                        session: session.id,
                                    })
                                "
                            >
                                <QrCode class="size-4" />
                                Show room QR
                            </Link>
                        </Button>
                    </div>
                </div>

                <Alert v-if="feedbackError" variant="destructive">
                    <AlertTitle>Session action failed</AlertTitle>
                    <AlertDescription>{{ feedbackError }}</AlertDescription>
                </Alert>

                <Alert v-else-if="feedbackMessage">
                    <AlertTitle>Session updated</AlertTitle>
                    <AlertDescription>{{ feedbackMessage }}</AlertDescription>
                </Alert>

                <div class="space-y-3">
                    <div>
                        <h3 class="font-medium">React live</h3>
                        <p class="text-sm text-muted-foreground">
                            {{ interactionHelpText }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-for="reaction in reactionTypes"
                            :key="reaction.type"
                            :dusk="`session-reaction-button-${reaction.type}`"
                            variant="outline"
                            :disabled="
                                ! viewer.can_interact
                                    || reactionRequest.processing
                            "
                            @click="handleReaction(reaction.type)"
                        >
                            {{ reaction.label }}
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
            <Card class="border-border/70 py-0 shadow-sm">
                <CardContent class="space-y-4 p-6">
                    <div class="flex items-center gap-2">
                        <Users class="size-4 text-muted-foreground" />
                        <h2 class="text-lg font-semibold">Participants</h2>
                    </div>

                    <div
                        v-if="participants.length > 0"
                        class="space-y-3"
                    >
                        <div
                            v-for="participant in participants"
                            :key="participant.id"
                            class="flex items-center gap-3 rounded-2xl border border-border/70 bg-background/80 p-3"
                        >
                            <Avatar class="size-10">
                                <AvatarFallback
                                    class="bg-primary/10 text-primary"
                                >
                                    {{ getInitials(participant.name) }}
                                </AvatarFallback>
                            </Avatar>

                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">
                                    {{ participant.name }}
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    {{
                                        participant.participant_type ===
                                        'physical'
                                            ? 'Physical attendee'
                                            : 'Remote attendee'
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <p v-else class="text-sm text-muted-foreground">
                        No active participants yet.
                    </p>
                </CardContent>
            </Card>

            <Card class="border-border/70 py-0 shadow-sm">
                <CardContent class="space-y-4 p-6">
                    <div class="flex items-center gap-2">
                        <MessageSquareText
                            class="size-4 text-muted-foreground"
                        />
                        <h2 class="text-lg font-semibold">Questions</h2>
                    </div>

                    <div
                        v-if="session.qa_enabled"
                        class="space-y-3 rounded-2xl border border-border/70 bg-background/80 p-4"
                    >
                        <label
                            for="session-question-body"
                            class="text-sm font-medium"
                        >
                            Ask the room
                        </label>
                        <textarea
                            id="session-question-body"
                            v-model="questionRequest.body"
                            name="body"
                            rows="4"
                            class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm shadow-xs transition outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                            placeholder="Type your question for the speaker or moderator."
                            :disabled="
                                ! viewer.can_interact
                                    || questionRequest.processing
                            "
                        />
                        <p
                            v-if="questionRequest.errors.body"
                            class="text-sm text-destructive"
                        >
                            {{ questionRequest.errors.body }}
                        </p>
                        <div
                            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <p class="text-sm text-muted-foreground">
                                {{ interactionHelpText }}
                            </p>
                            <Button
                                :disabled="
                                    ! viewer.can_interact
                                        || questionRequest.processing
                                "
                                @click="handleQuestionSubmit"
                            >
                                Submit question
                            </Button>
                        </div>
                    </div>

                    <p
                        v-else
                        class="rounded-2xl border border-dashed border-border/70 bg-background/60 p-4 text-sm text-muted-foreground"
                    >
                        Q&amp;A is turned off for this session.
                    </p>

                    <div v-if="questions.length > 0" class="space-y-3">
                        <div
                            v-for="question in questions"
                            :key="question.id"
                            class="rounded-2xl border border-border/70 bg-background/80 p-4"
                        >
                            <p class="font-medium">{{ question.body }}</p>

                            <div
                                class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <p class="text-sm text-muted-foreground">
                                    {{ question.user.name }} ·
                                    {{ question.votes_count }} vote{{
                                        question.votes_count !== 1 ? 's' : ''
                                    }}
                                </p>

                                <Button
                                    :dusk="`session-question-vote-button-${question.id}`"
                                    size="sm"
                                    variant="outline"
                                    :disabled="
                                        ! viewer.can_interact
                                            || question.viewer_has_voted
                                            || voteRequest.processing
                                    "
                                    @click="handleVote(question.id)"
                                >
                                    {{
                                        question.viewer_has_voted
                                            ? 'Voted'
                                            : 'Vote'
                                    }}
                                </Button>
                            </div>
                        </div>
                    </div>

                    <p v-else class="text-sm text-muted-foreground">
                        No questions yet.
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
