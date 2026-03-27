<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { store } from '@/actions/App/Http/Controllers/EventSetupController';

const currentStep = ref(1);
const totalSteps = 6;

const form = useForm({
    name: '',
    description: '',
    venue: '',
    starts_at: '',
    ends_at: '',
    allow_open_registration: false,
});

const tags = ref<string[]>([]);
const newTag = ref('');

function addTag() {
    const tag = newTag.value.trim();
    if (tag && !tags.value.includes(tag)) {
        tags.value.push(tag);
    }
    newTag.value = '';
}

function removeTag(index: number) {
    tags.value.splice(index, 1);
}

const icebreakers = ref<string[]>([]);
const newIcebreaker = ref('');

function addIcebreaker() {
    const q = newIcebreaker.value.trim();
    if (q) {
        icebreakers.value.push(q);
    }
    newIcebreaker.value = '';
}

function removeIcebreaker(index: number) {
    icebreakers.value.splice(index, 1);
}

interface Session {
    title: string;
    speaker: string;
    room: string;
    starts_at: string;
    ends_at: string;
}

const sessions = ref<Session[]>([]);
const newSession = ref<Session>({
    title: '',
    speaker: '',
    room: '',
    starts_at: '',
    ends_at: '',
});

function addSession() {
    if (newSession.value.title) {
        sessions.value.push({ ...newSession.value });
        newSession.value = { title: '', speaker: '', room: '', starts_at: '', ends_at: '' };
    }
}

function removeSession(index: number) {
    sessions.value.splice(index, 1);
}

interface Booth {
    name: string;
    company: string;
    description: string;
}

const booths = ref<Booth[]>([]);
const newBooth = ref<Booth>({ name: '', company: '', description: '' });

function addBooth() {
    if (newBooth.value.name) {
        booths.value.push({ ...newBooth.value });
        newBooth.value = { name: '', company: '', description: '' };
    }
}

function removeBooth(index: number) {
    booths.value.splice(index, 1);
}

function nextStep() {
    if (currentStep.value < totalSteps) {
        currentStep.value++;
    }
}

function prevStep() {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
}

function goToStep(step: number) {
    currentStep.value = step;
}

const canSubmit = computed(() => {
    return form.name && form.starts_at && form.ends_at;
});

function submit() {
    form.post(store.url());
}

const stepLabels = [
    'Event Details',
    'Interest Tags',
    'Icebreakers',
    'Sessions',
    'Booths',
    'Review & Launch',
];
</script>

<template>
    <div class="min-h-screen bg-background">
        <Head title="Create Event" />

        <div class="mx-auto max-w-3xl px-4 py-8">
            <h1 class="mb-8 text-3xl font-bold text-foreground">
                Create Event
            </h1>

            <!-- Step indicator -->
            <div class="mb-8 flex gap-2">
                <button
                    v-for="(label, i) in stepLabels"
                    :key="i"
                    class="flex-1 rounded-lg px-2 py-2 text-center text-xs font-medium transition-colors"
                    :class="
                        currentStep === i + 1
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-muted text-muted-foreground hover:bg-muted/80'
                    "
                    @click="goToStep(i + 1)"
                >
                    {{ label }}
                </button>
            </div>

            <!-- Step 1: Event Details -->
            <div v-show="currentStep === 1" class="space-y-4">
                <h2 class="text-xl font-semibold text-foreground">
                    Event Details
                </h2>

                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-foreground"
                        >Name *</label
                    >
                    <input
                        v-model="form.name"
                        type="text"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-foreground focus:border-primary focus:ring-1 focus:ring-primary"
                        placeholder="My Conference 2026"
                    />
                    <p
                        v-if="form.errors.name"
                        class="mt-1 text-sm text-destructive"
                    >
                        {{ form.errors.name }}
                    </p>
                </div>

                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-foreground"
                        >Description</label
                    >
                    <textarea
                        v-model="form.description"
                        rows="3"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-foreground focus:border-primary focus:ring-1 focus:ring-primary"
                        placeholder="What is this event about?"
                    />
                </div>

                <div>
                    <label
                        class="mb-1 block text-sm font-medium text-foreground"
                        >Venue</label
                    >
                    <input
                        v-model="form.venue"
                        type="text"
                        class="w-full rounded-lg border border-border bg-background px-3 py-2 text-foreground focus:border-primary focus:ring-1 focus:ring-primary"
                        placeholder="Convention Center, City"
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-foreground"
                            >Starts At *</label
                        >
                        <input
                            v-model="form.starts_at"
                            type="datetime-local"
                            class="w-full rounded-lg border border-border bg-background px-3 py-2 text-foreground focus:border-primary focus:ring-1 focus:ring-primary"
                        />
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-foreground"
                            >Ends At *</label
                        >
                        <input
                            v-model="form.ends_at"
                            type="datetime-local"
                            class="w-full rounded-lg border border-border bg-background px-3 py-2 text-foreground focus:border-primary focus:ring-1 focus:ring-primary"
                        />
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-foreground">
                    <input
                        v-model="form.allow_open_registration"
                        type="checkbox"
                        class="rounded border-border"
                    />
                    Allow open registration
                </label>
            </div>

            <!-- Step 2: Interest Tags -->
            <div v-show="currentStep === 2" class="space-y-4">
                <h2 class="text-xl font-semibold text-foreground">
                    Interest Tags
                </h2>
                <p class="text-sm text-muted-foreground">
                    Add tags that participants can use to find each other.
                </p>

                <div class="flex gap-2">
                    <input
                        v-model="newTag"
                        type="text"
                        class="flex-1 rounded-lg border border-border bg-background px-3 py-2 text-foreground focus:border-primary focus:ring-1 focus:ring-primary"
                        placeholder="e.g. AI, DevOps, Frontend"
                        @keydown.enter.prevent="addTag"
                    />
                    <button
                        class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        @click="addTag"
                    >
                        Add
                    </button>
                </div>

                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="(tag, i) in tags"
                        :key="i"
                        class="inline-flex items-center gap-1 rounded-full bg-muted px-3 py-1 text-sm text-foreground"
                    >
                        {{ tag }}
                        <button
                            class="ml-1 text-muted-foreground hover:text-destructive"
                            @click="removeTag(i)"
                        >
                            &times;
                        </button>
                    </span>
                </div>

                <p
                    v-if="tags.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    No tags added yet.
                </p>
            </div>

            <!-- Step 3: Icebreaker Questions -->
            <div v-show="currentStep === 3" class="space-y-4">
                <h2 class="text-xl font-semibold text-foreground">
                    Icebreaker Questions
                </h2>
                <p class="text-sm text-muted-foreground">
                    Questions shown to participants to spark conversation.
                </p>

                <div class="flex gap-2">
                    <input
                        v-model="newIcebreaker"
                        type="text"
                        class="flex-1 rounded-lg border border-border bg-background px-3 py-2 text-foreground focus:border-primary focus:ring-1 focus:ring-primary"
                        placeholder="What brought you to this event?"
                        @keydown.enter.prevent="addIcebreaker"
                    />
                    <button
                        class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        @click="addIcebreaker"
                    >
                        Add
                    </button>
                </div>

                <ul class="space-y-2">
                    <li
                        v-for="(q, i) in icebreakers"
                        :key="i"
                        class="flex items-center justify-between rounded-lg border border-border bg-muted/50 px-3 py-2"
                    >
                        <span class="text-sm text-foreground">{{ q }}</span>
                        <button
                            class="text-sm text-muted-foreground hover:text-destructive"
                            @click="removeIcebreaker(i)"
                        >
                            Remove
                        </button>
                    </li>
                </ul>

                <p
                    v-if="icebreakers.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    No icebreaker questions yet.
                </p>
            </div>

            <!-- Step 4: Sessions -->
            <div v-show="currentStep === 4" class="space-y-4">
                <h2 class="text-xl font-semibold text-foreground">
                    Session Schedule
                </h2>

                <div
                    class="space-y-3 rounded-lg border border-border bg-muted/30 p-4"
                >
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium text-muted-foreground"
                                >Title</label
                            >
                            <input
                                v-model="newSession.title"
                                type="text"
                                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground"
                                placeholder="Session title"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium text-muted-foreground"
                                >Speaker</label
                            >
                            <input
                                v-model="newSession.speaker"
                                type="text"
                                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground"
                                placeholder="Speaker name"
                            />
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium text-muted-foreground"
                                >Room</label
                            >
                            <input
                                v-model="newSession.room"
                                type="text"
                                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground"
                                placeholder="Room A"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium text-muted-foreground"
                                >Starts</label
                            >
                            <input
                                v-model="newSession.starts_at"
                                type="datetime-local"
                                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium text-muted-foreground"
                                >Ends</label
                            >
                            <input
                                v-model="newSession.ends_at"
                                type="datetime-local"
                                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground"
                            />
                        </div>
                    </div>
                    <button
                        class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        @click="addSession"
                    >
                        Add Session
                    </button>
                </div>

                <ul class="space-y-2">
                    <li
                        v-for="(s, i) in sessions"
                        :key="i"
                        class="flex items-center justify-between rounded-lg border border-border bg-muted/50 px-3 py-2"
                    >
                        <div>
                            <p class="text-sm font-medium text-foreground">
                                {{ s.title }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ s.speaker }} &middot; {{ s.room }}
                            </p>
                        </div>
                        <button
                            class="text-sm text-muted-foreground hover:text-destructive"
                            @click="removeSession(i)"
                        >
                            Remove
                        </button>
                    </li>
                </ul>

                <p
                    v-if="sessions.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    No sessions added yet.
                </p>
            </div>

            <!-- Step 5: Booths -->
            <div v-show="currentStep === 5" class="space-y-4">
                <h2 class="text-xl font-semibold text-foreground">
                    Booth Setup
                </h2>

                <div
                    class="space-y-3 rounded-lg border border-border bg-muted/30 p-4"
                >
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium text-muted-foreground"
                                >Booth Name</label
                            >
                            <input
                                v-model="newBooth.name"
                                type="text"
                                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground"
                                placeholder="Booth name"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium text-muted-foreground"
                                >Company</label
                            >
                            <input
                                v-model="newBooth.company"
                                type="text"
                                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground"
                                placeholder="Company name"
                            />
                        </div>
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-xs font-medium text-muted-foreground"
                            >Description</label
                        >
                        <textarea
                            v-model="newBooth.description"
                            rows="2"
                            class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground"
                            placeholder="What does this booth showcase?"
                        />
                    </div>
                    <button
                        class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        @click="addBooth"
                    >
                        Add Booth
                    </button>
                </div>

                <ul class="space-y-2">
                    <li
                        v-for="(b, i) in booths"
                        :key="i"
                        class="flex items-center justify-between rounded-lg border border-border bg-muted/50 px-3 py-2"
                    >
                        <div>
                            <p class="text-sm font-medium text-foreground">
                                {{ b.name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ b.company }}
                            </p>
                        </div>
                        <button
                            class="text-sm text-muted-foreground hover:text-destructive"
                            @click="removeBooth(i)"
                        >
                            Remove
                        </button>
                    </li>
                </ul>

                <p
                    v-if="booths.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    No booths added yet.
                </p>
            </div>

            <!-- Step 6: Review & Launch -->
            <div v-show="currentStep === 6" class="space-y-6">
                <h2 class="text-xl font-semibold text-foreground">
                    Review & Launch
                </h2>

                <div class="space-y-4 rounded-lg border border-border p-4">
                    <div>
                        <h3 class="text-sm font-medium text-muted-foreground">
                            Event
                        </h3>
                        <p class="text-lg font-semibold text-foreground">
                            {{ form.name || '(no name)' }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ form.venue || 'No venue' }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ form.starts_at }} &mdash; {{ form.ends_at }}
                        </p>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-muted-foreground">
                            Tags ({{ tags.length }})
                        </h3>
                        <div class="mt-1 flex flex-wrap gap-1">
                            <span
                                v-for="tag in tags"
                                :key="tag"
                                class="rounded-full bg-muted px-2 py-0.5 text-xs text-foreground"
                                >{{ tag }}</span
                            >
                            <span
                                v-if="tags.length === 0"
                                class="text-xs text-muted-foreground"
                                >None</span
                            >
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-muted-foreground">
                            Icebreakers ({{ icebreakers.length }})
                        </h3>
                        <ul class="mt-1 list-inside list-disc text-sm">
                            <li
                                v-for="q in icebreakers"
                                :key="q"
                                class="text-foreground"
                            >
                                {{ q }}
                            </li>
                        </ul>
                        <span
                            v-if="icebreakers.length === 0"
                            class="text-xs text-muted-foreground"
                            >None</span
                        >
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-muted-foreground">
                            Sessions ({{ sessions.length }})
                        </h3>
                        <ul class="mt-1 space-y-1">
                            <li
                                v-for="s in sessions"
                                :key="s.title"
                                class="text-sm text-foreground"
                            >
                                {{ s.title }} &mdash; {{ s.speaker }}
                                ({{ s.room }})
                            </li>
                        </ul>
                        <span
                            v-if="sessions.length === 0"
                            class="text-xs text-muted-foreground"
                            >None</span
                        >
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-muted-foreground">
                            Booths ({{ booths.length }})
                        </h3>
                        <ul class="mt-1 space-y-1">
                            <li
                                v-for="b in booths"
                                :key="b.name"
                                class="text-sm text-foreground"
                            >
                                {{ b.name }} ({{ b.company }})
                            </li>
                        </ul>
                        <span
                            v-if="booths.length === 0"
                            class="text-xs text-muted-foreground"
                            >None</span
                        >
                    </div>
                </div>

                <button
                    :disabled="!canSubmit || form.processing"
                    class="w-full rounded-lg bg-primary px-4 py-3 text-sm font-semibold text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                    @click="submit"
                >
                    {{ form.processing ? 'Creating...' : 'Launch Event' }}
                </button>
            </div>

            <!-- Navigation -->
            <div class="mt-8 flex justify-between">
                <button
                    v-if="currentStep > 1"
                    class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-foreground hover:bg-muted"
                    @click="prevStep"
                >
                    Previous
                </button>
                <div v-else />

                <button
                    v-if="currentStep < totalSteps"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                    @click="nextStep"
                >
                    Next
                </button>
            </div>
        </div>
    </div>
</template>
