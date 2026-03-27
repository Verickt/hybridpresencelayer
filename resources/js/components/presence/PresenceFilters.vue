<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { feed } from '@/routes/event';

const props = defineProps<{
    filters: { type?: string; status?: string; tag?: string };
    eventSlug: string;
}>();

const type = ref(props.filters.type ?? '');
const status = ref(props.filters.status ?? '');

function applyFilters() {
    router.visit(
        feed(
            { event: props.eventSlug },
            {
                query: {
                    type: type.value || null,
                    status: status.value || null,
                },
            },
        ),
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
}

watch([type, status], applyFilters);
</script>

<template>
    <div
        dusk="presence-filters"
        class="grid gap-3 rounded-2xl border border-border/70 bg-card/95 p-3 shadow-sm sm:grid-cols-2"
    >
        <label class="space-y-2">
            <span
                class="text-[11px] font-semibold tracking-[0.18em] text-muted-foreground uppercase"
            >
                Format
            </span>
            <select
                v-model="type"
                class="h-10 w-full rounded-xl border border-border bg-background px-3 text-sm transition outline-none focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/30"
            >
                <option value="">All Types</option>
                <option value="physical">Physical</option>
                <option value="remote" dusk="presence-filter-type-remote">
                    Remote
                </option>
            </select>
        </label>

        <label class="space-y-2">
            <span
                class="text-[11px] font-semibold tracking-[0.18em] text-muted-foreground uppercase"
            >
                Status
            </span>
            <select
                v-model="status"
                class="h-10 w-full rounded-xl border border-border bg-background px-3 text-sm transition outline-none focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/30"
            >
                <option value="">All Statuses</option>
                <option
                    value="available"
                    dusk="presence-filter-status-available"
                >
                    Available
                </option>
                <option value="in_session">In Session</option>
                <option value="at_booth">At Booth</option>
                <option value="busy" dusk="presence-filter-status-busy">
                    Busy
                </option>
            </select>
        </label>
    </div>
</template>
