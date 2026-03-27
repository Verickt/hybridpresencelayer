<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    CheckCircle2,
    MessageSquareText,
    MicVocal,
    Pin,
    Send,
    Sparkles,
    ThumbsUp,
    Users,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { getInitials } from '@/composables/useInitials';
import { tablet } from '@/routes/event/booths';
import { end, start } from '@/routes/event/booths/demos';
import {
    answer,
    followUp,
    pin as pinThread,
    store as storeThread,
    vote,
} from '@/routes/event/booths/threads';
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
    viewer_has_voted: boolean;
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
        content_links: Array<string | { label: string; url: string }> | null;
        interest_tags: string[];
    };
    viewer: {
        user_id: number;
        is_staff: boolean;
        can_post: boolean;
        can_moderate: boolean;
        participant_type: string | null;
    };
    active_demo: {
        id: number;
        title: string;
        status: string;
        starts_at: string | null;
        prompt_thread: BoothThread | null;
    } | null;
    pinned_thread: BoothThread | null;
    threads: BoothThread[];
    visitors: Array<{
        id: number;
        name: string;
        company: string;
        participant_type: string;
        entered_at: string;
    }>;
    staff: Array<{
        id: number;
        name: string;
        status: string | null;
    }>;
}>();

const resourceLinks = computed(() =>
    (props.booth.content_links ?? []).map((link) =>
        typeof link === 'string' ? { label: link, url: link } : link,
    ),
);

const boardHasContent = computed(
    () =>
        props.active_demo !== null ||
        props.pinned_thread !== null ||
        props.threads.length > 0,
);

const questionForm = useForm({
    body: '',
});

const demoForm = useForm({
    title: '',
});

const replyForm = useForm({
    body: '',
});

const replyingThreadId = ref<number | null>(null);

function formatTimestamp(timestamp: string | null): string {
    if (!timestamp) {
        return 'Just now';
    }

    return new Intl.DateTimeFormat([], {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(timestamp));
}

function submitQuestion(): void {
    questionForm.post(
        storeThread.url({
            event: props.event.slug,
            booth: props.booth.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => questionForm.reset(),
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

function voteForThread(threadId: number): void {
    router.post(
        vote.url({
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

function requestFollowUp(threadId: number): void {
    router.patch(
        followUp.url({
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

function canRequestFollowUp(thread: BoothThread): boolean {
    return (
        thread.user.id === props.viewer.user_id &&
        thread.follow_up_requested_at === null &&
        thread.kind === 'question'
    );
}

function refreshBoard(): void {
    router.reload({
        only: ['viewer', 'active_demo', 'pinned_thread', 'threads'],
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
        <Head :title="booth.name" />

        <Card
            class="border-border/70 bg-gradient-to-br from-primary/8 via-card to-card py-0 shadow-sm"
        >
            <CardContent class="space-y-4 p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="space-y-4">
                        <Heading
                            :title="booth.name"
                            :description="booth.description || booth.company"
                        />

                        <p class="text-sm font-medium text-foreground">
                            {{ booth.company }}
                        </p>
                    </div>

                    <Badge
                        v-if="active_demo"
                        class="rounded-full px-3 py-1 text-[11px] font-semibold tracking-[0.16em] uppercase"
                    >
                        Live Demo
                    </Badge>
                </div>

                <div class="flex flex-wrap gap-1.5">
                    <Badge
                        v-for="tag in booth.interest_tags"
                        :key="tag"
                        variant="outline"
                        class="rounded-full border-primary/15 bg-primary/5 px-2.5 py-1 text-[11px] font-medium text-primary"
                    >
                        {{ tag }}
                    </Badge>
                </div>

                <div v-if="resourceLinks.length" class="space-y-2">
                    <p class="text-sm font-medium">Resources</p>
                    <a
                        v-for="link in resourceLinks"
                        :key="link.url"
                        :href="link.url"
                        target="_blank"
                        rel="noreferrer"
                        class="block rounded-xl border border-border/70 bg-background/80 px-3 py-2 text-sm text-primary underline-offset-4 hover:underline"
                    >
                        <span class="font-medium">{{ link.label }}</span>
                        <span class="mt-1 block text-xs text-muted-foreground">
                            {{ link.url }}
                        </span>
                    </a>
                </div>

                <Button
                    v-if="viewer.can_moderate"
                    as-child
                    variant="outline"
                    class="rounded-full"
                >
                    <Link
                        :href="
                            tablet({
                                event: event.slug,
                                booth: booth.id,
                            })
                        "
                    >
                        Open tablet console
                    </Link>
                </Button>
            </CardContent>
        </Card>

        <Card class="border-border/70 py-0 shadow-sm">
            <CardContent class="space-y-5 p-6">
                <div
                    class="flex flex-wrap items-start justify-between gap-3"
                >
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <MessageSquareText
                                class="size-4 text-muted-foreground"
                            />
                            <h2 class="text-lg font-semibold">Booth Board</h2>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            Ask questions live during a demo or follow up after
                            you leave the booth.
                        </p>
                    </div>

                    <Badge
                        variant="outline"
                        class="rounded-full px-2.5 py-1 text-[11px]"
                    >
                        {{ viewer.is_staff ? 'Staff console' : 'Visitor view' }}
                    </Badge>
                </div>

                <div
                    v-if="viewer.can_moderate"
                    class="space-y-3 rounded-2xl border border-border/70 bg-background/80 p-4"
                >
                    <div class="flex items-center gap-2">
                        <MicVocal class="size-4 text-primary" />
                        <p class="font-medium">Live demo controls</p>
                    </div>

                    <div
                        v-if="active_demo"
                        class="flex flex-wrap items-center justify-between gap-3"
                    >
                        <div class="space-y-1">
                            <p class="font-medium">{{ active_demo.title }}</p>
                            <p class="text-sm text-muted-foreground">
                                Started
                                {{ formatTimestamp(active_demo.starts_at) }}
                            </p>
                        </div>

                        <Button
                            variant="outline"
                            class="rounded-full"
                            @click="endDemo(active_demo.id)"
                        >
                            End demo
                        </Button>
                    </div>

                    <form
                        v-else
                        class="flex flex-col gap-3 md:flex-row"
                        @submit.prevent="startDemo"
                    >
                        <Input
                            v-model="demoForm.title"
                            placeholder="Optional live demo title"
                            class="flex-1"
                        />
                        <Button
                            type="submit"
                            class="rounded-full"
                            :disabled="demoForm.processing"
                        >
                            Start demo
                        </Button>
                    </form>

                    <InputError :message="demoForm.errors.title" />
                </div>

                <form
                    v-if="viewer.can_post"
                    class="space-y-3 rounded-2xl border border-border/70 bg-background/80 p-4"
                    @submit.prevent="submitQuestion"
                >
                    <label class="block space-y-2">
                        <span class="text-sm font-medium">Ask the booth</span>
                        <textarea
                            v-model="questionForm.body"
                            rows="3"
                            class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-xl border bg-transparent px-3 py-2 text-sm outline-none focus-visible:ring-[3px]"
                            placeholder="What would you like the booth team to answer?"
                        />
                    </label>

                    <InputError :message="questionForm.errors.body" />

                    <div class="flex justify-end">
                        <Button
                            type="submit"
                            class="rounded-full"
                            :disabled="questionForm.processing"
                        >
                            <Send class="mr-2 size-4" />
                            Ask question
                        </Button>
                    </div>
                </form>

                <p v-else class="text-sm text-muted-foreground">
                    Join the event as a participant to post questions on the
                    booth board.
                </p>
            </CardContent>
        </Card>

        <Card
            v-if="active_demo"
            class="border-border/70 bg-primary/5 py-0 shadow-sm"
        >
            <CardContent class="space-y-3 p-6">
                <div class="flex flex-wrap items-center gap-2">
                    <Badge class="rounded-full px-2.5 py-1 text-[11px]">
                        Live
                    </Badge>
                    <h2 class="text-lg font-semibold">
                        {{ active_demo.title }}
                    </h2>
                </div>

                <p class="text-sm text-muted-foreground">
                    {{ active_demo.prompt_thread?.body }}
                </p>
            </CardContent>
        </Card>

        <Card
            v-if="pinned_thread"
            class="border-border/70 bg-amber-50/60 py-0 shadow-sm"
        >
            <CardContent class="space-y-4 p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <Pin class="size-4 text-amber-600" />
                            <p class="font-semibold">Pinned question</p>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            {{ pinned_thread.user.name }} asked
                            {{ formatTimestamp(pinned_thread.last_activity_at) }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <Badge
                            v-if="pinned_thread.is_answered"
                            variant="secondary"
                            class="rounded-full px-2.5 py-1 text-[11px]"
                        >
                            Answered
                        </Badge>
                        <Badge
                            variant="outline"
                            class="rounded-full px-2.5 py-1 text-[11px]"
                        >
                            {{ pinned_thread.votes_count }} vote{{
                                pinned_thread.votes_count !== 1 ? 's' : ''
                            }}
                        </Badge>
                    </div>
                </div>

                <p class="text-sm leading-6 text-foreground">
                    {{ pinned_thread.body }}
                </p>

                <div
                    v-if="pinned_thread.replies.length"
                    class="space-y-2 rounded-2xl border border-border/70 bg-background/90 p-4"
                >
                    <div
                        v-for="reply in pinned_thread.replies"
                        :key="reply.id"
                        class="rounded-xl bg-primary/5 p-3"
                    >
                        <p class="text-sm leading-6 text-foreground">
                            {{ reply.body }}
                        </p>
                        <p class="mt-2 text-xs text-muted-foreground">
                            {{ reply.user.name }} ·
                            {{ formatTimestamp(reply.created_at) }}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <div class="space-y-4">
            <Card
                v-for="thread in threads"
                :key="thread.id"
                class="border-border/70 py-0 shadow-sm"
            >
                <CardContent class="space-y-4 p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <Sparkles
                                    class="size-4 text-muted-foreground"
                                />
                                <p class="font-medium">
                                    {{ thread.user.name }}
                                </p>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                {{ formatTimestamp(thread.last_activity_at) }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <Badge
                                v-if="thread.is_answered"
                                variant="secondary"
                                class="rounded-full px-2.5 py-1 text-[11px]"
                            >
                                <CheckCircle2 class="mr-1 size-3.5" />
                                Answered
                            </Badge>
                            <Badge
                                v-if="thread.follow_up_requested_at"
                                variant="outline"
                                class="rounded-full px-2.5 py-1 text-[11px]"
                            >
                                Follow-up requested
                            </Badge>
                            <Badge
                                variant="outline"
                                class="rounded-full px-2.5 py-1 text-[11px]"
                            >
                                {{ thread.votes_count }} vote{{
                                    thread.votes_count !== 1 ? 's' : ''
                                }}
                            </Badge>
                        </div>
                    </div>

                    <p class="text-sm leading-6 text-foreground">
                        {{ thread.body }}
                    </p>

                    <div
                        v-if="thread.replies.length"
                        class="space-y-2 rounded-2xl border border-border/70 bg-background/90 p-4"
                    >
                        <div
                            v-for="reply in thread.replies"
                            :key="reply.id"
                            class="rounded-xl bg-primary/5 p-3"
                        >
                            <p class="text-sm leading-6 text-foreground">
                                {{ reply.body }}
                            </p>
                            <p class="mt-2 text-xs text-muted-foreground">
                                {{ reply.user.name }} ·
                                {{ formatTimestamp(reply.created_at) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Button
                            variant="outline"
                            class="rounded-full"
                            :disabled="thread.viewer_has_voted"
                            @click="voteForThread(thread.id)"
                        >
                            <ThumbsUp class="mr-2 size-4" />
                            {{ thread.viewer_has_voted ? 'Voted' : 'Upvote' }}
                        </Button>

                        <Button
                            v-if="canRequestFollowUp(thread)"
                            variant="outline"
                            class="rounded-full"
                            @click="requestFollowUp(thread.id)"
                        >
                            Request follow-up
                        </Button>

                        <template v-if="viewer.can_moderate">
                            <Button
                                variant="outline"
                                class="rounded-full"
                                @click="startReply(thread.id)"
                            >
                                Answer
                            </Button>

                            <Button
                                v-if="!thread.is_answered"
                                variant="outline"
                                class="rounded-full"
                                @click="markAnswered(thread.id)"
                            >
                                Mark answered
                            </Button>

                            <Button
                                variant="outline"
                                class="rounded-full"
                                @click="togglePin(thread.id)"
                            >
                                {{ thread.is_pinned ? 'Unpin' : 'Pin' }}
                            </Button>
                        </template>
                    </div>

                    <form
                        v-if="replyingThreadId === thread.id"
                        class="space-y-3 rounded-2xl border border-border/70 bg-background/80 p-4"
                        @submit.prevent="submitReply(thread.id)"
                    >
                        <label class="block space-y-2">
                            <span class="text-sm font-medium">
                                Staff reply
                            </span>
                            <textarea
                                v-model="replyForm.body"
                                rows="3"
                                class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-xl border bg-transparent px-3 py-2 text-sm outline-none focus-visible:ring-[3px]"
                                placeholder="Answer publicly so future visitors can benefit."
                            />
                        </label>

                        <InputError :message="replyForm.errors.body" />

                        <div class="flex justify-end gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                class="rounded-full"
                                @click="replyingThreadId = null"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                class="rounded-full"
                                :disabled="replyForm.processing"
                            >
                                Publish answer
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card
                v-if="!boardHasContent"
                class="border-dashed border-border/70 bg-card/80 py-0 shadow-sm"
            >
                <CardContent class="py-12 text-center">
                    <p class="font-medium">No questions yet.</p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Use the board to collect reusable answers from both
                        remote and physical visitors.
                    </p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <Card class="border-border/70 py-0 shadow-sm">
                <CardContent class="space-y-4 p-6">
                    <div class="flex items-center gap-2">
                        <Users class="size-4 text-muted-foreground" />
                        <h2 class="text-lg font-semibold">Visitors</h2>
                    </div>

                    <div v-if="visitors.length > 0" class="space-y-3">
                        <div
                            v-for="visitor in visitors"
                            :key="visitor.id"
                            class="flex items-center gap-3 rounded-2xl border border-border/70 bg-background/80 p-3"
                        >
                            <Avatar class="size-10">
                                <AvatarFallback
                                    class="bg-primary/10 text-primary"
                                >
                                    {{ getInitials(visitor.name) }}
                                </AvatarFallback>
                            </Avatar>

                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">
                                    {{ visitor.name }}
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    {{ visitor.company }}
                                </p>
                            </div>

                            <Badge
                                variant="secondary"
                                class="rounded-full px-2.5 py-1 text-[11px]"
                            >
                                {{
                                    visitor.participant_type === 'physical'
                                        ? 'Physical'
                                        : 'Remote'
                                }}
                            </Badge>
                        </div>
                    </div>

                    <p v-else class="text-sm text-muted-foreground">
                        No visible visitors right now.
                    </p>
                </CardContent>
            </Card>

            <Card class="border-border/70 py-0 shadow-sm">
                <CardContent class="space-y-4 p-6">
                    <h2 class="text-lg font-semibold">Staff</h2>

                    <div v-if="staff.length > 0" class="space-y-3">
                        <div
                            v-for="member in staff"
                            :key="member.id"
                            class="flex items-center gap-3 rounded-2xl border border-border/70 bg-background/80 p-3"
                        >
                            <Avatar class="size-10">
                                <AvatarFallback
                                    class="bg-primary/10 text-primary"
                                >
                                    {{ getInitials(member.name) }}
                                </AvatarFallback>
                            </Avatar>

                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">
                                    {{ member.name }}
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    {{ member.status || 'Offline' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <p v-else class="text-sm text-muted-foreground">
                        No booth staff listed yet.
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
