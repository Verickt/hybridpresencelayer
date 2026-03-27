<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { computed } from 'vue';
import { show } from '@/routes/event/booths';

type Booth = {
    id: number;
    name: string;
    company: string;
    description: string;
    interest_tags: string[];
    visitor_count: number;
    staff: Array<{ id: number; name: string }>;
    relevance: number;
};

const props = defineProps<{
    event: { id: number; name: string; slug: string };
    booths: Booth[];
}>();

const recommended = computed(() => props.booths.filter((b) => b.relevance > 0).sort((a, b) => b.relevance - a.relevance));
const popular = computed(() => props.booths.filter((b) => b.visitor_count > 0 && b.relevance === 0).sort((a, b) => b.visitor_count - a.visitor_count));
const rest = computed(() => props.booths.filter((b) => b.relevance === 0 && b.visitor_count === 0));

const initial = (name: string) => name.charAt(0).toUpperCase();

const tagColors = [
    'bg-indigo-50 text-indigo-700',
    'bg-emerald-50 text-emerald-700',
    'bg-amber-50 text-amber-700',
    'bg-sky-50 text-sky-700',
    'bg-rose-50 text-rose-700',
];

function tagColor(tag: string) {
    const hash = tag.split('').reduce((a, c) => a + c.charCodeAt(0), 0);
    return tagColors[hash % tagColors.length];
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Head :title="`${event.name} Booths`" />

        <div class="flex items-center gap-2">
            <button class="text-neutral-400" @click="router.visit(`/event/${event.slug}/feed`)">
                <ArrowLeft class="size-5" />
            </button>
            <h1 class="text-2xl font-bold text-neutral-900">Booths</h1>
        </div>

        <!-- Recommended section -->
        <div v-if="recommended.length > 0">
            <p class="mb-2 text-[11px] font-semibold tracking-wider text-indigo-500 uppercase">
                Recommended for you
            </p>

            <Link
                v-for="booth in recommended"
                :key="booth.id"
                :href="show({ event: props.event.slug, booth: booth.id }).url"
                class="mb-3 block rounded-2xl border border-indigo-100 bg-white p-4 shadow-sm"
            >
                <div class="flex items-start gap-3">
                    <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-lg font-bold text-indigo-700">
                        {{ initial(booth.name) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between">
                            <h2 class="font-semibold text-neutral-900">{{ booth.name }}</h2>
                            <span class="flex items-center gap-2 text-xs text-neutral-500">
                                📍 {{ booth.visitor_count }}
                                🌐 {{ booth.staff.length }}
                            </span>
                        </div>
                        <p class="text-sm text-neutral-500">{{ booth.company }}</p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <span
                                v-for="tag in booth.interest_tags"
                                :key="tag"
                                class="rounded-full px-2 py-0.5 text-[11px] font-medium"
                                :class="tagColor(tag)"
                            >
                                {{ tag }}
                            </span>
                            <span v-if="booth.staff.length > 0" class="rounded-full bg-green-50 px-2 py-0.5 text-[11px] font-medium text-green-700">
                                ✓ Staff available
                            </span>
                        </div>
                        <button class="mt-3 w-full rounded-full bg-indigo-600 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            Visit Booth
                        </button>
                    </div>
                </div>
            </Link>
        </div>

        <!-- Popular section -->
        <div v-if="popular.length > 0">
            <p class="mb-2 text-[11px] font-semibold tracking-wider text-neutral-400 uppercase">
                Popular now
            </p>
            <div class="space-y-2">
                <Link
                    v-for="booth in popular"
                    :key="booth.id"
                    :href="show({ event: props.event.slug, booth: booth.id }).url"
                    class="flex items-center gap-3 rounded-xl border border-neutral-100 bg-white p-3"
                >
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-base font-bold text-amber-700">
                        {{ initial(booth.name) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-neutral-900">{{ booth.name }}</p>
                        <p class="text-sm text-neutral-500">{{ booth.company }}</p>
                    </div>
                    <span class="text-xs text-neutral-500">
                        📍 {{ booth.visitor_count }} 🌐 {{ booth.staff.length }}
                    </span>
                </Link>
            </div>
        </div>

        <!-- All booths section -->
        <div v-if="rest.length > 0">
            <p class="mb-2 text-[11px] font-semibold tracking-wider text-neutral-400 uppercase">
                All booths
            </p>
            <div class="space-y-2">
                <Link
                    v-for="booth in rest"
                    :key="booth.id"
                    :href="show({ event: props.event.slug, booth: booth.id }).url"
                    class="flex items-center gap-3 rounded-xl border border-neutral-100 bg-white p-3"
                >
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-base font-bold text-neutral-600">
                        {{ initial(booth.name) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-neutral-900">{{ booth.name }}</p>
                        <p class="text-sm text-neutral-500">{{ booth.company }}</p>
                    </div>
                    <span class="text-xs text-neutral-500">
                        📍 {{ booth.visitor_count }} 🌐 {{ booth.staff.length }}
                    </span>
                </Link>
            </div>
        </div>

        <!-- All booths if no sections apply (no relevance data) -->
        <div v-if="recommended.length === 0 && popular.length === 0 && rest.length === 0 && booths.length > 0">
            <div class="space-y-2">
                <Link
                    v-for="booth in booths"
                    :key="booth.id"
                    :href="show({ event: props.event.slug, booth: booth.id }).url"
                    class="flex items-center gap-3 rounded-xl border border-neutral-100 bg-white p-3"
                >
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-base font-bold text-neutral-600">
                        {{ initial(booth.name) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-neutral-900">{{ booth.name }}</p>
                        <p class="text-sm text-neutral-500">{{ booth.company }}</p>
                    </div>
                    <span class="text-xs text-neutral-500">
                        📍 {{ booth.visitor_count }} 🌐 {{ booth.staff.length }}
                    </span>
                </Link>
            </div>
        </div>

        <p v-if="booths.length === 0" class="py-12 text-center text-sm text-neutral-400">
            No booths are listed yet.
        </p>
    </div>
</template>
