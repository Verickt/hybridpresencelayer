<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { feed } from '@/routes/event';

const props = defineProps<{
    filters: { type?: string; status?: string; tag?: string };
    eventSlug: string;
    availableTags?: string[];
}>();

const type = ref(props.filters.type ?? '');
const status = ref(props.filters.status ?? '');
const tag = ref(props.filters.tag ?? '');

function toggleFilter(filter: 'type' | 'status' | 'tag', value: string) {
    if (filter === 'type') {
        type.value = type.value === value ? '' : value;
    } else if (filter === 'status') {
        status.value = status.value === value ? '' : value;
    } else {
        tag.value = tag.value === value ? '' : value;
    }
}

function applyFilters() {
    router.visit(
        feed(
            { event: props.eventSlug },
            {
                query: {
                    type: type.value || null,
                    status: status.value || null,
                    tag: tag.value || null,
                },
            },
        ),
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
}

watch([type, status, tag], applyFilters);

const pills = [
    { filter: 'status' as const, value: 'available', label: 'Available' },
    { filter: 'type' as const, value: 'physical', label: 'Physical' },
    { filter: 'type' as const, value: 'remote', label: 'Remote' },
];
</script>

<template>
    <div dusk="presence-filters" class="flex flex-wrap gap-2">
        <button
            v-for="pill in pills"
            :key="pill.value"
            class="rounded-full border px-4 py-1.5 text-sm font-medium transition"
            :class="
                (pill.filter === 'type' ? type : status) === pill.value
                    ? 'border-primary bg-primary text-primary-foreground'
                    : 'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300'
            "
            :dusk="`presence-filter-${pill.filter}-${pill.value}`"
            @click="toggleFilter(pill.filter, pill.value)"
        >
            {{ pill.label }}
        </button>

        <button
            v-for="t in availableTags ?? []"
            :key="t"
            class="rounded-full border px-4 py-1.5 text-sm font-medium transition"
            :class="
                tag === t
                    ? 'border-primary bg-primary text-primary-foreground'
                    : 'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300'
            "
            @click="toggleFilter('tag', t)"
        >
            {{ t }}
        </button>
    </div>
</template>
