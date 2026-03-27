<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

const props = defineProps<{
    filters: { type?: string; status?: string; tag?: string }
    eventSlug: string
}>()

const type = ref(props.filters.type ?? '')
const status = ref(props.filters.status ?? '')

function applyFilters() {
    const params: Record<string, string> = {}
    if (type.value) params.type = type.value
    if (status.value) params.status = status.value

    router.get(route('event.feed', { event: props.eventSlug, ...params }), {}, {
        preserveState: true,
        preserveScroll: true,
    })
}

watch([type, status], applyFilters)
</script>

<template>
    <div class="flex items-center gap-3">
        <select v-model="type" class="rounded-md border px-3 py-1.5 text-sm">
            <option value="">All Types</option>
            <option value="physical">Physical</option>
            <option value="remote">Remote</option>
        </select>

        <select v-model="status" class="rounded-md border px-3 py-1.5 text-sm">
            <option value="">All Statuses</option>
            <option value="available">Available</option>
            <option value="in_session">In Session</option>
            <option value="at_booth">At Booth</option>
        </select>
    </div>
</template>
