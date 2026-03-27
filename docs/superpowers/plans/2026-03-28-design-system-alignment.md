# Design System Alignment — Paper → Implementation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Align the frontend implementation with the Paper design system — indigo primary color, mobile-first layout, compact participant rows, filter pills, and proper bottom tab bar.

**Architecture:** Change the CSS custom properties to use indigo as primary. Rewrite the Feed page to match the Paper design (search bar, filter pills, featured suggestion card, compact participant rows). Restyle Connections and Profile pages. Update BottomTabs with colored icons and badges.

**Tech Stack:** Vue 3, Tailwind CSS v4, Inertia.js v3, shadcn-vue components

---

### Task 1: Change Primary Color to Indigo

**Files:**
- Modify: `resources/css/app.css:92-127` (`:root` block)

- [ ] **Step 1: Update CSS custom properties**

Change the `:root` primary color from black to indigo-600, and ring to indigo-500:

```css
/* In :root block, replace these lines: */
--primary: hsl(239 84% 67%);          /* was hsl(0 0% 9%) — indigo-500 #6366F1 */
--primary-foreground: hsl(0 0% 100%); /* was hsl(0 0% 98%) — pure white on indigo */
--ring: hsl(239 84% 67%);             /* was hsl(0 0% 3.9%) — match primary */
```

- [ ] **Step 2: Build and verify**

Run: `npm run build`

- [ ] **Step 3: Verify in browser**

Navigate to the feed page. All previously-black primary buttons should now be indigo. Confirm the Ping button, badges, and active tab are indigo.

- [ ] **Step 4: Commit**

```bash
git add resources/css/app.css
git commit -m "style: change primary color from black to indigo to match design system"
```

---

### Task 2: Update Font to Inter

**Files:**
- Modify: `resources/css/app.css:11-14` (font-sans definition)

The Paper design uses Inter. The app currently uses Instrument Sans.

- [ ] **Step 1: Install Inter font**

```bash
npm install @fontsource-variable/inter
```

- [ ] **Step 2: Import Inter in app.ts**

Add at the top of `resources/js/app.ts`:

```ts
import '@fontsource-variable/inter';
```

- [ ] **Step 3: Update CSS font stack**

In `resources/css/app.css`, replace the `--font-sans` value in the `@theme inline` block:

```css
--font-sans:
    'Inter Variable', 'Inter', ui-sans-serif, system-ui, sans-serif,
    'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol',
    'Noto Color Emoji';
```

And update the duplicate in `@layer utilities`:

```css
--font-sans:
    'Inter Variable', 'Inter', ui-sans-serif, system-ui, sans-serif,
    'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol',
    'Noto Color Emoji';
```

- [ ] **Step 4: Build and verify**

Run: `npm run build`

- [ ] **Step 5: Commit**

```bash
git add resources/css/app.css resources/js/app.ts package.json package-lock.json
git commit -m "style: switch font from Instrument Sans to Inter to match design system"
```

---

### Task 3: Rewrite PresenceFilters as Filter Pills

**Files:**
- Modify: `resources/js/components/presence/PresenceFilters.vue`

Replace the dropdown selects with horizontal pill buttons matching the Paper design. The design shows: Available, Physical, Remote, plus interest tag pills (like AI/ML).

- [ ] **Step 1: Rewrite PresenceFilters.vue**

```vue
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
```

- [ ] **Step 2: Build and verify filters render as pills**

Run: `npm run build`

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/presence/PresenceFilters.vue
git commit -m "style: replace filter dropdowns with horizontal pill buttons per design"
```

---

### Task 4: Create Compact ParticipantRow Component

**Files:**
- Create: `resources/js/components/presence/ParticipantRow.vue`

The Paper design shows participants as compact horizontal rows: avatar (with status dot) + name + title + tags on the left, ping icon on the right. This replaces the verbose card layout for the "PEOPLE" list.

- [ ] **Step 1: Create ParticipantRow.vue**

```vue
<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';

const props = defineProps<{
    participant: {
        id: number;
        name: string;
        company?: string;
        role_title?: string;
        participant_type: string;
        status: string;
        context_badge: string | null;
        interest_tags: string[];
    };
}>();

defineEmits<{
    ping: [userId: number];
}>();

const initials = computed(() => getInitials(props.participant.name));

const statusColors: Record<string, string> = {
    available: 'bg-green-500',
    in_session: 'bg-blue-500',
    at_booth: 'bg-purple-500',
    busy: 'bg-amber-500',
    away: 'bg-neutral-400',
};

const statusColor = computed(() => statusColors[props.participant.status] ?? 'bg-neutral-400');

const avatarColors = [
    'bg-rose-100 text-rose-700',
    'bg-indigo-100 text-indigo-700',
    'bg-emerald-100 text-emerald-700',
    'bg-amber-100 text-amber-700',
    'bg-sky-100 text-sky-700',
    'bg-purple-100 text-purple-700',
];

const avatarColor = computed(() => {
    const hash = props.participant.name.split('').reduce((acc, c) => acc + c.charCodeAt(0), 0);
    return avatarColors[hash % avatarColors.length];
});

// Show first 3 tags, with matching ones styled differently
const displayTags = computed(() => props.participant.interest_tags.slice(0, 3));
</script>

<template>
    <div
        :dusk="`presence-row-${participant.id}`"
        class="flex items-center gap-3 border-b border-neutral-100 py-3 last:border-b-0"
    >
        <!-- Avatar with status dot -->
        <div class="relative shrink-0">
            <Avatar class="size-10">
                <AvatarFallback
                    class="text-sm font-semibold"
                    :class="avatarColor"
                >
                    {{ initials }}
                </AvatarFallback>
            </Avatar>
            <span
                class="absolute -bottom-0.5 -left-0.5 size-3 rounded-full border-2 border-white"
                :class="statusColor"
            />
        </div>

        <!-- Info -->
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
                <span class="truncate text-sm font-semibold text-neutral-900">
                    {{ participant.name }}
                </span>
                <span v-if="participant.role_title" class="truncate text-sm text-neutral-500">
                    {{ participant.role_title }}
                </span>
                <!-- Context badge inline -->
                <span
                    v-if="participant.context_badge"
                    class="shrink-0 rounded bg-indigo-50 px-1.5 py-0.5 text-[11px] font-medium text-indigo-600"
                >
                    {{ participant.context_badge }}
                </span>
            </div>
            <div class="mt-0.5 flex flex-wrap gap-1">
                <span
                    v-for="t in displayTags"
                    :key="t"
                    class="rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-medium text-neutral-600"
                >
                    {{ t }}
                </span>
            </div>
        </div>

        <!-- Ping action -->
        <button
            class="shrink-0 text-xl"
            @click="$emit('ping', participant.id)"
        >
            👋
        </button>
    </div>
</template>
```

- [ ] **Step 2: Build and verify no errors**

Run: `npm run build`

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/presence/ParticipantRow.vue
git commit -m "feat: add compact ParticipantRow component matching Paper design"
```

---

### Task 5: Rewrite Feed Page Layout

**Files:**
- Modify: `resources/js/pages/Event/Feed.vue`

Replace the stats dashboard + verbose cards with the Paper design: search bar at top, filter pills, "RIGHT NOW" featured suggestion, compact participant rows.

- [ ] **Step 1: Rewrite Feed.vue**

```vue
<script setup lang="ts">
import { Head, Link, router, useHttp } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref, watch } from 'vue';
import ParticipantRow from '@/components/presence/ParticipantRow.vue';
import PresenceFilters from '@/components/presence/PresenceFilters.vue';
import { useHaptics } from '@/composables/useHaptics';
import { ping } from '@/routes/event';
import { search } from '@/routes/event';

const { ping: hapticPing } = useHaptics();

const props = defineProps<{
    event: { id: number; name: string; slug: string };
    participants: Array<{
        id: number;
        name: string;
        company?: string;
        role_title?: string;
        intent?: string;
        participant_type: string;
        status: string;
        context_badge: string | null;
        icebreaker_answer?: string;
        open_to_call: boolean;
        interest_tags: string[];
    }>;
    suggestion?: {
        id: number;
        name: string;
        company?: string;
        role_title?: string;
        participant_type: string;
        status: string;
        interest_tags: string[];
        shared_tags: string[];
        shared_context?: string;
        time_left?: string;
    } | null;
    filters: { type?: string; status?: string; tag?: string };
    availableTags?: string[];
}>();

const liveParticipants = ref([...props.participants]);

watch(
    () => props.participants,
    (updated) => {
        liveParticipants.value = [...updated];
    },
);

const pingRequest = useHttp();

async function handlePing(userId: number) {
    hapticPing();
    try {
        await pingRequest.submit(
            ping({ event: props.event.slug, user: userId }),
        );
    } catch {
        // silently fail
    }
}

const lastOccurredAt = ref<Record<number, string>>({});

onMounted(() => {
    if (!window.Echo) {
        return;
    }

    const channel = window.Echo.join(`event.${props.event.id}.presence`);

    channel
        .here(() => {})
        .joining((user: { id: number; name: string }) => {
            if (!liveParticipants.value.find((p) => p.id === user.id)) {
                router.reload({ only: ['participants'] });
            }
        })
        .leaving(() => {});

    channel.listen(
        'PresenceStateChanged',
        (data: {
            user_id: number;
            status: string;
            context_badge: string | null;
            participant_type: string;
            occurred_at: string;
        }) => {
            const last = lastOccurredAt.value[data.user_id];

            if (last && data.occurred_at < last) {
                return;
            }

            lastOccurredAt.value[data.user_id] = data.occurred_at;

            const participant = liveParticipants.value.find(
                (p) => p.id === data.user_id,
            );

            if (participant) {
                participant.status = data.status;
                participant.context_badge = data.context_badge;
            }
        },
    );
});

onUnmounted(() => {
    window.Echo?.leave(`event.${props.event.id}.presence`);
});
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Head :title="`${event.name} Feed`" />

        <!-- Search bar -->
        <Link
            :href="`/event/${event.slug}/search`"
            class="flex items-center gap-2 rounded-full border border-neutral-200 bg-neutral-50 px-4 py-2.5 text-sm text-neutral-400 transition hover:border-neutral-300"
        >
            <Search class="size-4" />
            Search people, tags...
        </Link>

        <!-- Filter pills -->
        <PresenceFilters
            :filters="filters"
            :event-slug="event.slug"
            :available-tags="availableTags"
        />

        <!-- RIGHT NOW — Featured suggestion -->
        <div v-if="suggestion" class="space-y-2">
            <div class="flex items-center gap-1.5 text-xs font-semibold tracking-wider text-indigo-500 uppercase">
                <span>✨</span> Right now
            </div>

            <div class="rounded-2xl border border-indigo-100 bg-white p-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="relative shrink-0">
                        <div class="flex size-12 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700">
                            {{ suggestion.name.split(' ').map(n => n[0]).join('') }}
                        </div>
                        <span class="absolute -bottom-0.5 -left-0.5 size-3 rounded-full border-2 border-white bg-green-500" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-base font-semibold text-neutral-900">{{ suggestion.name }}</span>
                                <p class="text-sm text-neutral-500">
                                    {{ suggestion.role_title }}{{ suggestion.company ? `, ${suggestion.company}` : '' }}
                                    · {{ suggestion.participant_type === 'physical' ? '📍' : '🌐' }} {{ suggestion.participant_type === 'physical' ? 'Physical' : 'Remote' }}
                                </p>
                            </div>
                            <span v-if="suggestion.time_left" class="text-xs text-neutral-400">{{ suggestion.time_left }}</span>
                        </div>

                        <p class="mt-1 text-sm text-indigo-600">
                            {{ suggestion.shared_context || `Both interested in ${suggestion.shared_tags.join(' · ')}` }}
                        </p>

                        <div class="mt-3 flex items-center gap-2">
                            <button
                                class="flex flex-1 items-center justify-center gap-1.5 rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700"
                                @click="handlePing(suggestion.id)"
                            >
                                👋 Ping
                            </button>
                            <button
                                class="rounded-full border border-neutral-200 bg-white px-4 py-2 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50"
                            >
                                Later
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- People list -->
        <div>
            <p class="mb-2 text-xs font-semibold tracking-wider text-neutral-400 uppercase">
                People · {{ liveParticipants.length }} here
            </p>

            <div class="rounded-2xl bg-white">
                <ParticipantRow
                    v-for="participant in liveParticipants"
                    :key="participant.id"
                    :participant="participant"
                    @ping="handlePing"
                />

                <p
                    v-if="liveParticipants.length === 0"
                    class="py-12 text-center text-sm text-neutral-400"
                >
                    No participants match your filters.
                </p>
            </div>
        </div>
    </div>
</template>
```

- [ ] **Step 2: Check if `search` route export exists**

Run: `grep -r "export.*search" resources/js/routes/event/` — if it doesn't exist, use a hardcoded href for now (the Link already uses a hardcoded path).

- [ ] **Step 3: Build and verify**

Run: `npm run build`

- [ ] **Step 4: Verify in browser**

Navigate to the feed page. Should see: search bar, filter pills, people list with compact rows.

- [ ] **Step 5: Commit**

```bash
git add resources/js/pages/Event/Feed.vue
git commit -m "style: rewrite Feed page to match Paper design — search bar, pills, compact rows"
```

---

### Task 6: Update Bottom Tab Bar

**Files:**
- Modify: `resources/js/components/navigation/BottomTabs.vue`

The Paper design shows colored icons when active (indigo), a date badge on Sessions ("17"), and a notification count on Connections. The icons also differ from the current ones.

- [ ] **Step 1: Rewrite BottomTabs.vue**

```vue
<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    eventSlug: string;
}>();

const page = usePage();
const currentUrl = computed(() => page.url);

const base = computed(() => `/event/${props.eventSlug}`);

const today = new Date().getDate();

const tabs = computed(() => [
    {
        name: 'Feed',
        href: `${base.value}/feed`,
        active: currentUrl.value.includes('/feed'),
        icon: 'feed',
    },
    {
        name: 'Sessions',
        href: `${base.value}/sessions`,
        active: currentUrl.value.includes('/sessions'),
        icon: 'sessions',
        badge: String(today),
    },
    {
        name: 'Connections',
        href: `${base.value}/connections`,
        active: currentUrl.value.includes('/connections'),
        icon: 'connections',
    },
    {
        name: 'Profile',
        href: `${base.value}/profile`,
        active: currentUrl.value.includes('/profile'),
        icon: 'profile',
    },
]);
</script>

<template>
    <nav class="fixed bottom-0 left-0 right-0 z-50 border-t border-neutral-100 bg-white safe-area-pb">
        <div class="flex items-center justify-around py-1">
            <Link
                v-for="tab in tabs"
                :key="tab.name"
                :href="tab.href"
                class="relative flex min-w-[64px] flex-col items-center gap-0.5 px-3 py-2 text-[11px] font-medium transition-colors"
                :class="tab.active ? 'text-indigo-600' : 'text-neutral-400'"
            >
                <!-- Feed icon -->
                <svg v-if="tab.icon === 'feed'" class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                </svg>

                <!-- Sessions icon (calendar with date) -->
                <div v-else-if="tab.icon === 'sessions'" class="relative">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                        <line x1="16" y1="2" x2="16" y2="6" />
                        <line x1="8" y1="2" x2="8" y2="6" />
                        <line x1="3" y1="10" x2="21" y2="10" />
                    </svg>
                    <span
                        v-if="tab.badge"
                        class="absolute -right-2 -top-1 flex size-4 items-center justify-center rounded bg-red-500 text-[8px] font-bold text-white"
                    >
                        {{ tab.badge }}
                    </span>
                </div>

                <!-- Connections icon (handshake/chat) -->
                <svg v-else-if="tab.icon === 'connections'" class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                </svg>

                <!-- Profile icon -->
                <svg v-else-if="tab.icon === 'profile'" class="size-6" viewBox="0 0 24 24" fill="none" :fill="tab.active ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                </svg>

                <span>{{ tab.name }}</span>
            </Link>
        </div>
    </nav>
</template>
```

- [ ] **Step 2: Build and verify**

Run: `npm run build`

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/navigation/BottomTabs.vue
git commit -m "style: update bottom tabs with indigo active color and date badge per design"
```

---

### Task 7: Update EventLayout Header

**Files:**
- Modify: `resources/js/layouts/EventLayout.vue`

The Paper design shows a clean top bar: event name on left, notification bell (yellow with red badge) on right. No border — just clean.

- [ ] **Step 1: Update EventLayout.vue header**

Replace the header section:

```html
<header class="sticky top-0 z-40 bg-white safe-area-pt">
    <div class="flex items-center justify-between px-4 py-3">
        <h1 class="text-xl font-bold truncate text-neutral-900">{{ eventName }}</h1>
        <NotificationBell />
    </div>
</header>
```

Remove the `border-b` and `backdrop-blur` classes, make the font bolder and larger.

- [ ] **Step 2: Update NotificationBell to match design**

In `resources/js/components/navigation/NotificationBell.vue`, the Paper design shows a yellow/amber bell icon. Update:

```vue
<template>
    <button class="relative p-2" @click="$emit('open')">
        <span class="text-xl">🔔</span>
        <span
            v-if="unreadCount > 0"
            class="absolute -top-0.5 right-0 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white"
        >
            {{ unreadCount > 9 ? '9+' : unreadCount }}
        </span>
    </button>
</template>
```

- [ ] **Step 3: Build and verify**

Run: `npm run build`

- [ ] **Step 4: Commit**

```bash
git add resources/js/layouts/EventLayout.vue resources/js/components/navigation/NotificationBell.vue
git commit -m "style: update event header and notification bell to match Paper design"
```

---

### Task 8: Restyle Connections Page

**Files:**
- Modify: `resources/js/pages/Event/Connections.vue`

The Paper design shows: "Connections" heading + "3 people you've matched with" subtitle, then compact rows with avatar, name, title + match context, tags, and chat + call icons on the right.

- [ ] **Step 1: Update the Connections page template**

Replace the connection list section (keep the chat overlay as-is):

```vue
<!-- Replace the Heading and connection list -->
<div class="space-y-1">
    <h1 class="text-2xl font-bold text-neutral-900">Connections</h1>
    <p class="text-sm text-neutral-500">{{ connections.length }} people you've matched with</p>
</div>

<!-- Connection list -->
<div>
    <div
        v-for="connection in connections"
        :key="connection.connection_id"
        class="flex items-center gap-3 border-b border-neutral-100 py-3 last:border-b-0"
    >
        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700">
            {{ connection.user.name.split(' ').map((n: string) => n[0]).join('') }}
        </div>

        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-neutral-900">{{ connection.user.name }}</p>
            <p class="truncate text-sm text-neutral-500">
                {{ connection.user.company }}{{ connection.context ? ` · ${connection.context}` : '' }}
            </p>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            <button
                class="flex size-9 items-center justify-center rounded-full border border-neutral-200 text-neutral-500 transition hover:bg-neutral-50"
                @click="openChat(connection.connection_id)"
            >
                <MessageCircle class="size-4" />
            </button>
            <button
                class="flex size-9 items-center justify-center rounded-full border border-neutral-200 text-neutral-500 transition hover:bg-neutral-50"
            >
                <Phone class="size-4" />
            </button>
        </div>
    </div>
</div>

<p v-if="connections.length === 0" class="py-8 text-center text-sm text-neutral-400">
    No connections yet. Start meeting people!
</p>
```

Remove the `<Heading>` component import and usage. Remove the `Card` / `CardContent` imports and wrappers around connection items.

- [ ] **Step 2: Build and verify**

Run: `npm run build`

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/Event/Connections.vue
git commit -m "style: restyle Connections page with compact rows and icon buttons per design"
```

---

### Task 9: Restyle Profile Page

**Files:**
- Modify: `resources/js/pages/Event/Profile.vue`

The Paper design shows a centered layout: large avatar circle, name, "Role · Company", attendance type + status, interest tags as indigo pills, Edit Profile button. Below: Availability section (Serendipity Mode toggle, Invisible Mode toggle, Attending as), Notifications section.

- [ ] **Step 1: Update Profile page template**

Replace the content:

```vue
<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Head :title="`${event.name} - Profile`" />

        <!-- Profile header — centered -->
        <div class="flex flex-col items-center pt-4">
            <div class="flex size-20 items-center justify-center rounded-full bg-indigo-100 text-2xl font-bold text-indigo-700">
                {{ user.name.split(' ').map(n => n[0]).join('') }}
            </div>
            <h1 class="mt-3 text-xl font-bold text-neutral-900">{{ user.name }}</h1>
            <p class="text-sm text-neutral-500">{{ user.role_title }} · {{ user.company }}</p>
            <p class="mt-1 text-sm text-neutral-400">
                📍 {{ user.participant_type === 'physical' ? 'Physical' : 'Remote' }} · {{ user.status === 'available' ? 'Available' : user.status.replace('_', ' ') }}
            </p>

            <div class="mt-3 flex flex-wrap justify-center gap-2">
                <span
                    v-for="tag in interestTags"
                    :key="tag"
                    class="rounded-full bg-indigo-600 px-3 py-1 text-xs font-medium text-white"
                >
                    {{ tag }}
                </span>
            </div>

            <button class="mt-4 rounded-full border border-neutral-200 px-6 py-2 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50">
                Edit Profile
            </button>
        </div>

        <!-- Availability section -->
        <div class="space-y-1">
            <p class="text-xs font-semibold tracking-wider text-neutral-400 uppercase">Availability</p>
            <div class="divide-y divide-neutral-100">
                <div class="flex items-center justify-between py-3">
                    <span class="text-sm text-neutral-700">Serendipity Mode</span>
                    <div class="relative h-6 w-11 rounded-full bg-indigo-600">
                        <div class="absolute right-0.5 top-0.5 size-5 rounded-full bg-white shadow transition" />
                    </div>
                </div>
                <div class="flex items-center justify-between py-3">
                    <span class="text-sm text-neutral-700">Invisible Mode</span>
                    <div class="relative h-6 w-11 rounded-full bg-neutral-200">
                        <div class="absolute left-0.5 top-0.5 size-5 rounded-full bg-white shadow transition" />
                    </div>
                </div>
                <div class="flex items-center justify-between py-3">
                    <span class="text-sm text-neutral-700">Attending as</span>
                    <span class="text-sm text-neutral-500">📍 {{ user.participant_type === 'physical' ? 'Physical' : 'Remote' }} ›</span>
                </div>
            </div>
        </div>

        <!-- Notifications section -->
        <div class="space-y-1">
            <p class="text-xs font-semibold tracking-wider text-neutral-400 uppercase">Notifications</p>
            <div class="divide-y divide-neutral-100">
                <div class="flex items-center justify-between py-3">
                    <span class="text-sm text-neutral-700">Do Not Disturb</span>
                    <div class="relative h-6 w-11 rounded-full bg-neutral-200">
                        <div class="absolute left-0.5 top-0.5 size-5 rounded-full bg-white shadow transition" />
                    </div>
                </div>
                <div class="flex items-center justify-between py-3">
                    <span class="text-sm text-neutral-700">Notification Preferences</span>
                    <span class="text-sm text-neutral-400">›</span>
                </div>
            </div>
        </div>

        <!-- QR Scanner section -->
        <Card class="shadow-sm">
            <CardContent class="space-y-3 p-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold">QR Scanner</h3>
                    <Button
                        size="sm"
                        :variant="showScanner ? 'outline' : 'default'"
                        @click="showScanner = !showScanner"
                    >
                        <Camera class="mr-1 size-4" />
                        {{ showScanner ? 'Close' : 'Scan QR' }}
                    </Button>
                </div>

                <p class="text-sm text-muted-foreground">
                    Scan session or booth QR codes to check in instantly.
                </p>

                <QrScanner
                    v-if="showScanner"
                    @scan="handleScan"
                />

                <p v-if="scanResult" class="text-sm font-medium text-primary">
                    {{ scanResult }}
                </p>
                <p v-if="scanError" class="text-sm text-destructive">
                    {{ scanError }}
                </p>
            </CardContent>
        </Card>
    </div>
</template>
```

- [ ] **Step 2: Build and verify**

Run: `npm run build`

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/Event/Profile.vue
git commit -m "style: restyle Profile page with centered layout and sections per Paper design"
```

---

### Task 10: Update ParticipantAvatar Colors

**Files:**
- Modify: `resources/js/components/presence/ParticipantAvatar.vue`

The Paper design uses pastel/muted avatar colors grouped by domain (Tech=teal, Business=blue, Design=rose, etc.). Update the color mapping to use softer colors with light backgrounds.

- [ ] **Step 1: Update avatar color scheme**

Replace the `tagColors` map and template:

```vue
<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';
import ActivityPulse from './ActivityPulse.vue';

const props = defineProps<{
    name: string;
    interestTags: string[];
    activityLevel?: number;
    size?: 'sm' | 'md' | 'lg';
}>();

const sizeClasses: Record<string, string> = {
    sm: 'size-8 text-xs',
    md: 'size-10 text-sm',
    lg: 'size-12 text-base',
};

const avatarColors = [
    'bg-teal-100 text-teal-700',
    'bg-indigo-100 text-indigo-700',
    'bg-rose-100 text-rose-700',
    'bg-amber-100 text-amber-700',
    'bg-sky-100 text-sky-700',
    'bg-emerald-100 text-emerald-700',
];

const initials = computed(() => getInitials(props.name));

const colorClass = computed(() => {
    const hash = props.name.split('').reduce((acc, c) => acc + c.charCodeAt(0), 0);
    return avatarColors[hash % avatarColors.length];
});
</script>

<template>
    <Avatar class="relative shrink-0" :class="sizeClasses[size ?? 'md']">
        <AvatarFallback
            class="font-semibold"
            :class="colorClass"
        >
            {{ initials }}
        </AvatarFallback>
        <ActivityPulse v-if="activityLevel" :level="activityLevel" />
    </Avatar>
</template>
```

- [ ] **Step 2: Build and verify**

Run: `npm run build`

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/presence/ParticipantAvatar.vue
git commit -m "style: update avatar colors to pastel palette matching Paper design"
```

---

### Task 11: Update EventLayout Background

**Files:**
- Modify: `resources/js/layouts/EventLayout.vue`

The Paper design has a subtle warm gray/off-white background (`bg-neutral-50` or similar), not pure white.

- [ ] **Step 1: Update background color**

In EventLayout.vue, change:
```html
<div class="min-h-screen bg-background pb-16">
```
to:
```html
<div class="min-h-screen bg-neutral-50 pb-16">
```

- [ ] **Step 2: Build and verify**

Run: `npm run build`

- [ ] **Step 3: Commit**

```bash
git add resources/js/layouts/EventLayout.vue
git commit -m "style: use neutral-50 background for event pages per design"
```

---

### Task 12: Final Visual Verification

- [ ] **Step 1: Full build**

Run: `npm run build`

- [ ] **Step 2: Run existing tests to ensure nothing is broken**

Run: `php artisan test --compact`

- [ ] **Step 3: Lint PHP**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 4: Browser verification**

Navigate through all pages and compare with Paper designs:
1. Feed — search bar, filter pills, compact rows
2. Connections — compact rows, chat/call icons
3. Profile — centered layout, sections
4. Bottom tabs — indigo active, date badge

- [ ] **Step 5: Final commit if any adjustments needed**

```bash
git add -A
git commit -m "style: final design alignment adjustments"
```
