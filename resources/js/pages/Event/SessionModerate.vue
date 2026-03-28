<script setup lang="ts">
import { Head, router, useHttp } from '@inertiajs/vue3';
import { CheckCircle, ChevronDown, ChevronUp, EyeOff, Eye, Pin, MessageSquare, Users, Zap, BarChart2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { getInitials } from '@/composables/useInitials';

type Reply = {
    id: number;
    body: string;
    user: { id: number; name: string };
    votes_count: number;
    is_speaker: boolean;
    is_organizer: boolean;
    created_at: string;
};

type Question = {
    id: number;
    body: string;
    user: { id: number; name: string };
    votes_count: number;
    is_answered: boolean;
    is_pinned: boolean;
    is_hidden: boolean;
    answered_by: number | null;
    replies: Reply[];
};

type ReactionBucket = {
    time_window: number;
    type: string;
    count: number;
};

const props = defineProps<{
    event: { id: number; name: string; slug: string };
    session: {
        id: number;
        title: string;
        speaker: string | null;
        starts_at: string;
        ends_at: string;
        is_live: boolean;
    };
    questions: Question[];
    reactionBuckets: ReactionBucket[];
    stats: {
        total_reactions: number;
        total_questions: number;
        physical_count: number;
        remote_count: number;
        total_participants: number;
    };
}>();

const expandedQuestions = ref<Set<number>>(new Set());
const replyBodies = ref<Record<number, string>>({});
const replyRequests: Record<number, ReturnType<typeof useHttp>> = {};

const actionRequest = useHttp();

function toggleExpand(questionId: number) {
    if (expandedQuestions.value.has(questionId)) {
        expandedQuestions.value.delete(questionId);
    } else {
        expandedQuestions.value.add(questionId);
    }
}

function reloadData() {
    router.reload({ only: ['questions', 'stats'], preserveScroll: true });
}

function pin(question: Question) {
    router.post(
        `/event/${props.event.slug}/sessions/${props.session.id}/questions/${question.id}/pin`,
        {},
        { preserveScroll: true, onSuccess: reloadData },
    );
}

function hide(question: Question) {
    router.post(
        `/event/${props.event.slug}/sessions/${props.session.id}/questions/${question.id}/hide`,
        {},
        { preserveScroll: true, onSuccess: reloadData },
    );
}

function markAnswered(question: Question) {
    router.post(
        `/event/${props.event.slug}/sessions/${props.session.id}/questions/${question.id}/answer`,
        {},
        { preserveScroll: true, onSuccess: reloadData },
    );
}

function submitReply(question: Question) {
    const body = replyBodies.value[question.id]?.trim();
    if (!body) return;

    router.post(
        `/event/${props.event.slug}/sessions/${props.session.id}/questions/${question.id}/replies`,
        { body },
        {
            preserveScroll: true,
            onSuccess: () => {
                replyBodies.value[question.id] = '';
                reloadData();
            },
        },
    );
}

const reactionColors: Record<string, string> = {
    lightbulb: 'bg-yellow-400',
    clap: 'bg-green-400',
    question: 'bg-blue-400',
    fire: 'bg-orange-400',
    think: 'bg-purple-400',
};

function reactionBucketsForWindow(): Map<number, ReactionBucket[]> {
    const map = new Map<number, ReactionBucket[]>();
    for (const bucket of props.reactionBuckets) {
        const w = bucket.time_window ?? 0;
        if (!map.has(w)) map.set(w, []);
        map.get(w)!.push(bucket);
    }
    return map;
}

function maxBucketCount(): number {
    const map = reactionBucketsForWindow();
    let max = 0;
    for (const buckets of map.values()) {
        const total = buckets.reduce((sum, b) => sum + b.count, 0);
        if (total > max) max = total;
    }
    return max || 1;
}

function windowLabel(w: number): string {
    const seconds = w * 30;
    const min = Math.floor(seconds / 60);
    const sec = seconds % 60;
    return `${min}:${sec.toString().padStart(2, '0')}`;
}

const bucketMap = reactionBucketsForWindow();
const windows = Array.from(bucketMap.keys()).sort((a, b) => a - b);
const maxCount = maxBucketCount();
</script>

<template>
    <Head :title="`Moderate: ${session.title}`" />

    <div class="min-h-screen bg-neutral-50">
        <!-- Header -->
        <div class="border-b border-neutral-200 bg-white px-4 py-3">
            <div class="mx-auto max-w-7xl">
                <div class="flex items-center gap-3">
                    <a
                        :href="`/event/${event.slug}/sessions/${session.id}`"
                        class="text-neutral-400 hover:text-neutral-600"
                    >
                        ← Back
                    </a>
                    <div class="flex-1">
                        <h1 class="text-base font-semibold text-neutral-900">{{ session.title }}</h1>
                        <div class="mt-0.5 flex items-center gap-2 text-xs text-neutral-500">
                            <span v-if="session.speaker">{{ session.speaker }}</span>
                            <span
                                v-if="session.is_live"
                                class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700"
                            >
                                <span class="size-1.5 rounded-full bg-red-500"></span>
                                Live
                            </span>
                        </div>
                    </div>
                    <div class="text-xs text-neutral-400">Organizer Moderation</div>
                </div>
            </div>
        </div>

        <!-- Body -->
        <div class="mx-auto max-w-7xl px-4 py-4">
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <!-- Left: Reaction Heatmap + Participants -->
                <div class="space-y-4">
                    <!-- Stats summary -->
                    <div class="rounded-xl border border-neutral-200 bg-white p-4">
                        <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold text-neutral-700">
                            <Users class="size-4" />
                            Participants
                        </h2>
                        <div class="grid grid-cols-3 gap-3 text-center">
                            <div>
                                <div class="text-xl font-bold text-neutral-900">{{ stats.total_participants }}</div>
                                <div class="text-xs text-neutral-500">Total</div>
                            </div>
                            <div>
                                <div class="text-xl font-bold text-indigo-600">{{ stats.physical_count }}</div>
                                <div class="text-xs text-neutral-500">Physical</div>
                            </div>
                            <div>
                                <div class="text-xl font-bold text-emerald-600">{{ stats.remote_count }}</div>
                                <div class="text-xs text-neutral-500">Remote</div>
                            </div>
                        </div>
                    </div>

                    <!-- Reaction heatmap -->
                    <div class="rounded-xl border border-neutral-200 bg-white p-4">
                        <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold text-neutral-700">
                            <BarChart2 class="size-4" />
                            Reactions Over Time
                            <span class="ml-auto text-xs font-normal text-neutral-400">{{ stats.total_reactions }} total</span>
                        </h2>

                        <div v-if="windows.length === 0" class="py-6 text-center text-sm text-neutral-400">
                            No reactions yet
                        </div>

                        <div v-else class="flex items-end gap-0.5 overflow-x-auto pb-2">
                            <div
                                v-for="w in windows"
                                :key="w"
                                class="flex min-w-[8px] flex-col-reverse gap-px"
                                :title="windowLabel(w)"
                            >
                                <div
                                    v-for="bucket in bucketMap.get(w)"
                                    :key="bucket.type"
                                    :class="[reactionColors[bucket.type] ?? 'bg-neutral-300', 'w-2 rounded-sm']"
                                    :style="{ height: `${Math.max(2, (bucket.count / maxCount) * 48)}px` }"
                                ></div>
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="mt-2 flex flex-wrap gap-2">
                            <div v-for="(color, type) in reactionColors" :key="type" class="flex items-center gap-1">
                                <span :class="[color, 'size-2 rounded-sm']"></span>
                                <span class="text-xs capitalize text-neutral-500">{{ type }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Center: Q&A Feed -->
                <div class="lg:col-span-1">
                    <div class="rounded-xl border border-neutral-200 bg-white">
                        <div class="flex items-center justify-between border-b border-neutral-100 px-4 py-3">
                            <h2 class="flex items-center gap-2 text-sm font-semibold text-neutral-700">
                                <MessageSquare class="size-4" />
                                Q&A
                            </h2>
                            <span class="text-xs text-neutral-400">{{ stats.total_questions }} visible</span>
                        </div>

                        <div v-if="questions.length === 0" class="px-4 py-8 text-center text-sm text-neutral-400">
                            No questions yet
                        </div>

                        <div v-else class="divide-y divide-neutral-100">
                            <div v-for="question in questions" :key="question.id" class="p-4">
                                <!-- Question header -->
                                <div class="flex items-start gap-2">
                                    <div
                                        class="flex size-7 shrink-0 items-center justify-center rounded-full bg-neutral-100 text-xs font-medium text-neutral-600"
                                    >
                                        {{ getInitials(question.user.name) }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5 text-xs text-neutral-500">
                                            <span class="font-medium text-neutral-700">{{ question.user.name }}</span>
                                            <span class="ml-auto font-semibold text-neutral-600">{{ question.votes_count }}↑</span>
                                        </div>
                                        <p class="mt-0.5 text-sm text-neutral-800" :class="{ 'opacity-50': question.is_hidden }">
                                            {{ question.body }}
                                        </p>

                                        <!-- Badges -->
                                        <div class="mt-1.5 flex flex-wrap gap-1">
                                            <span
                                                v-if="question.is_pinned"
                                                class="inline-flex items-center gap-0.5 rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700"
                                            >
                                                <Pin class="size-2.5" /> Pinned
                                            </span>
                                            <span
                                                v-if="question.is_answered"
                                                class="inline-flex items-center gap-0.5 rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700"
                                            >
                                                <CheckCircle class="size-2.5" /> Answered
                                            </span>
                                            <span
                                                v-if="question.is_hidden"
                                                class="inline-flex items-center gap-0.5 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-600"
                                            >
                                                <EyeOff class="size-2.5" /> Hidden
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action buttons -->
                                <div class="mt-2 flex flex-wrap gap-1.5 pl-9">
                                    <button
                                        class="rounded-md border border-neutral-200 bg-neutral-50 px-2 py-1 text-xs font-medium text-neutral-600 hover:bg-neutral-100"
                                        @click="pin(question)"
                                    >
                                        {{ question.is_pinned ? 'Unpin' : 'Pin' }}
                                    </button>
                                    <button
                                        class="rounded-md border border-neutral-200 bg-neutral-50 px-2 py-1 text-xs font-medium text-neutral-600 hover:bg-neutral-100"
                                        @click="hide(question)"
                                    >
                                        {{ question.is_hidden ? 'Show' : 'Hide' }}
                                    </button>
                                    <button
                                        v-if="!question.is_answered"
                                        class="rounded-md border border-green-200 bg-green-50 px-2 py-1 text-xs font-medium text-green-700 hover:bg-green-100"
                                        @click="markAnswered(question)"
                                    >
                                        Mark Answered
                                    </button>
                                    <button
                                        class="rounded-md border border-neutral-200 bg-neutral-50 px-2 py-1 text-xs font-medium text-neutral-600 hover:bg-neutral-100"
                                        @click="toggleExpand(question.id)"
                                    >
                                        <span class="flex items-center gap-1">
                                            {{ question.replies.length }} repl{{ question.replies.length === 1 ? 'y' : 'ies' }}
                                            <ChevronDown v-if="!expandedQuestions.has(question.id)" class="size-3" />
                                            <ChevronUp v-else class="size-3" />
                                        </span>
                                    </button>
                                </div>

                                <!-- Replies -->
                                <div v-if="expandedQuestions.has(question.id)" class="mt-2 space-y-2 pl-9">
                                    <div
                                        v-for="reply in question.replies"
                                        :key="reply.id"
                                        class="rounded-lg bg-neutral-50 p-2.5 text-xs"
                                    >
                                        <div class="flex items-center gap-1 text-neutral-500">
                                            <span class="font-medium text-neutral-700">{{ reply.user.name }}</span>
                                            <span v-if="reply.is_speaker" class="rounded bg-indigo-100 px-1 text-indigo-600">Speaker</span>
                                            <span v-if="reply.is_organizer" class="rounded bg-amber-100 px-1 text-amber-600">Organizer</span>
                                            <span class="ml-auto">{{ reply.votes_count }}↑</span>
                                        </div>
                                        <p class="mt-0.5 text-neutral-800">{{ reply.body }}</p>
                                    </div>

                                    <!-- Reply form -->
                                    <div class="flex gap-1.5">
                                        <input
                                            v-model="replyBodies[question.id]"
                                            type="text"
                                            placeholder="Add organizer reply…"
                                            class="flex-1 rounded-lg border border-neutral-200 bg-white px-2.5 py-1.5 text-xs outline-none focus:border-indigo-400 focus:ring-1 focus:ring-indigo-200"
                                            @keydown.enter.prevent="submitReply(question)"
                                        />
                                        <button
                                            class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                                            :disabled="!replyBodies[question.id]?.trim()"
                                            @click="submitReply(question)"
                                        >
                                            Reply
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Engagement Summary -->
                <div class="space-y-4">
                    <div class="rounded-xl border border-neutral-200 bg-white p-4">
                        <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold text-neutral-700">
                            <Zap class="size-4" />
                            Engagement Summary
                        </h2>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-neutral-600">Total Reactions</span>
                                <span class="text-sm font-semibold text-neutral-900">{{ stats.total_reactions }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-neutral-600">Visible Questions</span>
                                <span class="text-sm font-semibold text-neutral-900">{{ stats.total_questions }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-neutral-600">Answered</span>
                                <span class="text-sm font-semibold text-neutral-900">
                                    {{ questions.filter((q) => q.is_answered).length }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-neutral-600">Hidden</span>
                                <span class="text-sm font-semibold text-red-600">
                                    {{ questions.filter((q) => q.is_hidden).length }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-neutral-600">Pinned</span>
                                <span class="text-sm font-semibold text-indigo-600">
                                    {{ questions.filter((q) => q.is_pinned).length }}
                                </span>
                            </div>
                            <hr class="border-neutral-100" />
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-neutral-600">Physical Attendees</span>
                                <span class="text-sm font-semibold text-indigo-600">{{ stats.physical_count }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-neutral-600">Remote Attendees</span>
                                <span class="text-sm font-semibold text-emerald-600">{{ stats.remote_count }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
