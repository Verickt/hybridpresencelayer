# Plan 9: PWA Shell — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the app installable as a PWA with web app manifest, bottom tab navigation, mobile-first responsive layout, push notification permission flow, and QR camera scanning.

**Architecture:** Add a web app manifest served dynamically per event (for branding, with `Cache-Control: private, no-store` to prevent CDN leakage). Create a mobile-first app shell layout with bottom tab navigation. Integrate Web Push via VAPID keys. QR scanning uses the `getUserMedia` API with a lightweight JS scanner library, but the client never trusts or navigates the raw scan result directly. Instead, scanned payloads are posted to a dedicated server endpoint that accepts only temporary signed relative URLs for allowed event-scoped actions, then validates event scope, expiry, signature, and authorization before executing or rejecting the action. PWA caching is limited to static assets and installability only — no caching of authenticated/event-scoped data for MVP.

**Tech Stack:** Laravel 13, Inertia v3, Vue 3, Tailwind CSS v4

**Depends on:** Plan 1 (models), Plan 2 (presence — feed page), Plan 8 (notifications)

## TDD Standard

- Start each task with failing tests before implementation.
- Cover negative and edge cases: non-event pages, invalid QR payloads, camera denial, unsupported browsers, missing notification permission, and unauthorized manifest/start URLs.
- For manifest and tab destinations, add endpoint tests for headers, payload shape, and route safety.
- Add browser smoke coverage plus real browser tests for install-shell navigation and one scanner/permission failure path.

---

## Task 1: Web App Manifest

**Files:**
- Create: `app/Http/Controllers/ManifestController.php`
- Modify: `routes/web.php`
- Modify: `resources/views/app.blade.php` (add manifest link)

- [ ] **Step 1: Create ManifestController**

```php
// app/Http/Controllers/ManifestController.php
<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\JsonResponse;

class ManifestController extends Controller
{
    public function __invoke(Event $event): JsonResponse
    {
        return response()->json([
            'name' => $event->name,
            'short_name' => substr($event->name, 0, 12),
            'description' => $event->description ?? 'Hybrid Presence Platform',
            'start_url' => route('event.feed', $event),
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => $event->theme_color,
            'orientation' => 'portrait',
            'icons' => [
                ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
            ],
        ])->header('Cache-Control', 'private, no-store');
    }
}
```

- [ ] **Step 2: Add route**

```php
Route::get('/event/{event:slug}/manifest.json', ManifestController::class)->name('event.manifest');
```

- [ ] **Step 3: Add manifest link to app.blade.php**

In `resources/views/app.blade.php`, add in the `<head>`:

```html
<link rel="manifest" href="{{ route('event.manifest', request()->route('event') ?? 'default') }}">
<meta name="theme-color" content="#3B82F6">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
```

- [ ] **Step 4: Create placeholder icons**

Run: `mkdir -p public/icons`

Create simple placeholder PNG files (these will be replaced with event-specific icons later).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/ManifestController.php routes/web.php resources/views/app.blade.php public/icons/
git commit -m "feat: add dynamic PWA manifest with per-event branding"
```

---

## Task 2: Mobile-First App Shell Layout

**Files:**
- Create: `resources/js/layouts/EventLayout.vue`
- Create: `resources/js/components/navigation/BottomTabs.vue`
- Create: `resources/js/components/navigation/NotificationBell.vue`

- [ ] **Step 1: Create BottomTabs component**

```vue
<!-- resources/js/components/navigation/BottomTabs.vue -->
<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { Users, Calendar, MessageCircle, UserCircle } from 'lucide-vue-next'

const props = defineProps<{
    eventSlug: string
}>()

const page = usePage()
const currentUrl = computed(() => page.url)

const tabs = computed(() => [
    {
        name: 'Feed',
        icon: Users,
        href: route('event.feed', { event: props.eventSlug }),
        active: currentUrl.value.includes('/feed'),
    },
    {
        name: 'Sessions',
        icon: Calendar,
        href: route('event.sessions', { event: props.eventSlug }),
        active: currentUrl.value.includes('/sessions'),
    },
    {
        name: 'Connections',
        icon: MessageCircle,
        href: route('event.connections', { event: props.eventSlug }),
        active: currentUrl.value.includes('/connections'),
    },
    {
        name: 'Profile',
        icon: UserCircle,
        href: route('event.profile', { event: props.eventSlug }),
        active: currentUrl.value.includes('/profile'),
    },
])
</script>

<template>
    <nav class="fixed bottom-0 left-0 right-0 z-50 border-t bg-background safe-area-pb">
        <div class="flex items-center justify-around">
            <a
                v-for="tab in tabs"
                :key="tab.name"
                :href="tab.href"
                class="flex flex-col items-center gap-0.5 px-3 py-2 text-xs transition-colors min-w-[64px]"
                :class="tab.active ? 'text-primary' : 'text-muted-foreground'"
            >
                <component :is="tab.icon" class="h-5 w-5" />
                <span>{{ tab.name }}</span>
            </a>
        </div>
    </nav>
</template>
```

- [ ] **Step 2: Create NotificationBell component**

```vue
<!-- resources/js/components/navigation/NotificationBell.vue -->
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Bell } from 'lucide-vue-next'

const unreadCount = ref(0)

async function fetchCount() {
    try {
        const response = await fetch(route('notifications.count'))
        const data = await response.json()
        unreadCount.value = data.count
    } catch {
        // Silently fail
    }
}

onMounted(() => {
    fetchCount()
    setInterval(fetchCount, 30000) // Poll every 30s
})
</script>

<template>
    <button class="relative p-2" @click="$emit('open')">
        <Bell class="h-5 w-5" />
        <span
            v-if="unreadCount > 0"
            class="absolute -top-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] text-white"
        >
            {{ unreadCount > 9 ? '9+' : unreadCount }}
        </span>
    </button>
</template>
```

- [ ] **Step 3: Create EventLayout**

```vue
<!-- resources/js/layouts/EventLayout.vue -->
<script setup lang="ts">
import BottomTabs from '@/components/navigation/BottomTabs.vue'
import NotificationBell from '@/components/navigation/NotificationBell.vue'

defineProps<{
    eventName: string
    eventSlug: string
}>()
</script>

<template>
    <div class="min-h-screen bg-background pb-16">
        <!-- Top header -->
        <header class="sticky top-0 z-40 border-b bg-background/95 backdrop-blur safe-area-pt">
            <div class="flex items-center justify-between px-4 py-3">
                <h1 class="text-lg font-semibold truncate">{{ eventName }}</h1>
                <NotificationBell />
            </div>
        </header>

        <!-- Main content -->
        <main>
            <slot />
        </main>

        <!-- Bottom navigation -->
        <BottomTabs :event-slug="eventSlug" />
    </div>
</template>

<style>
.safe-area-pt {
    padding-top: env(safe-area-inset-top);
}

.safe-area-pb {
    padding-bottom: env(safe-area-inset-bottom);
}
</style>
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/layouts/EventLayout.vue resources/js/components/navigation/
git commit -m "feat: add mobile-first event layout with bottom tabs and notification bell"
```

---

## Task 3: QR Resolve Endpoint

**Files:**
- Create: `app/Http/Controllers/QrResolveController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Http/QrResolveControllerTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Http/QrResolveControllerTest.php
<?php

use App\Models\Booth;
use App\Models\BoothVisit;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionCheckIn;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\Fluent\AssertableJson;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->participant = User::factory()->create();
    $this->event->participants()->attach($this->participant, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
    $this->session = EventSession::factory()->live()->create(['event_id' => $this->event->id]);
    $this->booth = Booth::factory()->create(['event_id' => $this->event->id]);
});

it('requires authentication for qr resolve requests', function () {
    $this->postJson(route('event.qr.resolve', $this->event), [])
        ->assertUnauthorized();
});

it('resolves a valid signed session qr payload into a real check-in', function () {
    $payload = URL::temporarySignedRoute(
        'event.sessions.qr-checkin',
        now()->addMinutes(10),
        ['event' => $this->event, 'session' => $this->session],
        absolute: false,
    );

    $this->actingAs($this->participant)
        ->postJson(route('event.qr.resolve', $this->event), ['payload' => $payload])
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('action', 'session_check_in')
            ->where('target.type', 'session')
            ->where('target.id', $this->session->id)
            ->etc()
        );

    expect(SessionCheckIn::count())->toBe(1);
});

it('rejects expired, cross-event, unsupported, and malformed qr payloads', function () {
    // Add dedicated tests for expired signatures, wrong event scope, unsupported signed routes,
    // and malformed / external payloads.
});
```

- [ ] **Step 2: Add dedicated QR resolve route**

```php
Route::post('/event/{event:slug}/qr/resolve', QrResolveController::class)
    ->middleware('auth')
    ->name('event.qr.resolve');
```

- [ ] **Step 3: Add signed QR action routes**

Use temporary signed **relative** URLs for the QR payload itself. These routes are not opened directly by the scanner UI; they are only recognized as allowed signed targets by the resolve endpoint.

```php
Route::scopeBindings()->group(function () {
    Route::get('/event/{event:slug}/sessions/{session}/qr-checkin', fn () => abort(204))
        ->middleware('signed:relative')
        ->name('event.sessions.qr-checkin');

    Route::get('/event/{event:slug}/booths/{booth}/qr-checkin', fn () => abort(204))
        ->middleware('signed:relative')
        ->name('event.booths.qr-checkin');
});
```

- [ ] **Step 4: Implement `QrResolveController`**

`QrResolveController` should:

- validate the incoming `payload` as a relative application URL
- reject malformed or external payloads with `422`
- reject expired signed payloads with `410`
- reject valid payloads that belong to another event with `403`
- reject signed payloads that resolve to unsupported routes with `422`
- accept only known QR actions such as session check-in and booth check-in
- execute the underlying event-scoped action server-side and return a minimal JSON payload

Example response contract:

```json
{
  "message": "Checked in",
  "action": "session_check_in",
  "target": {
    "type": "session",
    "id": 42,
    "title": "Zero Trust Keynote"
  }
}
```

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/QrResolveController.php routes/web.php tests/Feature/Http/QrResolveControllerTest.php
git commit -m "feat: add server-side qr resolve endpoint with signed event actions"
```

---

## Task 4: QR Scanner Component

**Files:**
- Create: `resources/js/components/qr/QrScanner.vue`

- [ ] **Step 1: Install QR scanning library**

Run: `npm install qr-scanner`

- [ ] **Step 2: Create QrScanner component**

```vue
<!-- resources/js/components/qr/QrScanner.vue -->
<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import QrScanner from 'qr-scanner'

const emit = defineEmits<{
    scan: [result: string]
    error: [message: string]
}>()

const videoRef = ref<HTMLVideoElement>()
const scanner = ref<QrScanner>()
const hasCamera = ref(true)

onMounted(async () => {
    if (!videoRef.value) return

    try {
        const cameras = await QrScanner.listCameras()
        if (cameras.length === 0) {
            hasCamera.value = false
            return
        }

        scanner.value = new QrScanner(
            videoRef.value,
            (result) => {
                emit('scan', result.data)
                scanner.value?.stop()
            },
            {
                preferredCamera: 'environment',
                highlightScanRegion: true,
            }
        )

        await scanner.value.start()
    } catch {
        hasCamera.value = false
        emit('error', 'Camera access denied')
    }
})

onUnmounted(() => {
    scanner.value?.destroy()
})
</script>

<template>
    <div class="relative overflow-hidden rounded-xl bg-black">
        <video v-if="hasCamera" ref="videoRef" class="w-full" />
        <div v-else class="flex items-center justify-center p-8 text-white">
            <p>Camera not available. Use manual check-in instead.</p>
        </div>
    </div>
</template>
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/qr/QrScanner.vue package.json package-lock.json
git commit -m "feat: add QR scanner component for session/booth check-in"
```

---

## Task 5: Connection List & Profile Pages (Tab Destinations)

**Files:**
- Create: `app/Http/Controllers/ConnectionListController.php`
- Create: `app/Http/Controllers/EventProfileController.php`
- Create: `resources/js/pages/Event/Connections.vue`
- Create: `resources/js/pages/Event/Profile.vue`
- Modify: `routes/web.php`

- [ ] **Step 1: Create ConnectionListController**

Run: `php artisan make:controller ConnectionListController --no-interaction`

```php
// app/Http/Controllers/ConnectionListController.php
<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConnectionListController extends Controller
{
    public function __invoke(Request $request, Event $event): Response
    {
        $userId = $request->user()->id;

        $connections = Connection::where('event_id', $event->id)
            ->where(fn ($q) => $q->where('user_a_id', $userId)->orWhere('user_b_id', $userId))
            ->with(['userA:id,name,company', 'userB:id,name,company'])
            ->latest()
            ->get()
            ->map(function (Connection $c) use ($userId) {
                $other = $c->user_a_id === $userId ? $c->userB : $c->userA;

                return [
                    'connection_id' => $c->id,
                    'user' => [
                        'id' => $other->id,
                        'name' => $other->name,
                        'company' => $other->company,
                    ],
                    'context' => $c->context,
                    'is_cross_world' => $c->is_cross_world,
                    'created_at' => $c->created_at->toISOString(),
                ];
            });

        return Inertia::render('Event/Connections', [
            'event' => ['id' => $event->id, 'name' => $event->name, 'slug' => $event->slug],
            'connections' => $connections,
        ]);
    }
}
```

- [ ] **Step 2: Create EventProfileController**

Run: `php artisan make:controller EventProfileController --no-interaction`

```php
// app/Http/Controllers/EventProfileController.php
<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventProfileController extends Controller
{
    public function __invoke(Request $request, Event $event): Response
    {
        $user = $request->user();
        $pivot = $user->events()->where('event_id', $event->id)->first()?->pivot;
        $tags = $user->interestTags()->wherePivot('event_id', $event->id)->pluck('name');

        return Inertia::render('Event/Profile', [
            'event' => ['id' => $event->id, 'name' => $event->name, 'slug' => $event->slug],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company' => $user->company,
                'role_title' => $user->role_title,
                'intent' => $user->intent,
                'participant_type' => $pivot?->participant_type,
                'status' => $pivot?->status,
                'icebreaker_answer' => $pivot?->icebreaker_answer,
                'notification_mode' => $pivot?->notification_mode ?? 'normal',
                'is_invisible' => $user->is_invisible,
            ],
            'interestTags' => $tags,
        ]);
    }
}
```

- [ ] **Step 3: Create Vue pages**

```vue
<!-- resources/js/pages/Event/Connections.vue -->
<script setup lang="ts">
import EventLayout from '@/layouts/EventLayout.vue'

defineProps<{
    event: { id: number; name: string; slug: string }
    connections: Array<{
        connection_id: number
        user: { id: number; name: string; company?: string }
        context: string
        is_cross_world: boolean
        created_at: string
    }>
}>()
</script>

<template>
    <EventLayout :event-name="event.name" :event-slug="event.slug">
        <div class="mx-auto max-w-2xl px-4 py-6">
            <h2 class="text-xl font-bold mb-4">Your Connections</h2>

            <div class="space-y-3">
                <div
                    v-for="conn in connections"
                    :key="conn.connection_id"
                    class="flex items-center justify-between rounded-xl border p-4"
                >
                    <div>
                        <h3 class="font-semibold">{{ conn.user.name }}</h3>
                        <p v-if="conn.user.company" class="text-sm text-muted-foreground">{{ conn.user.company }}</p>
                        <p class="text-xs text-muted-foreground">{{ conn.context }}</p>
                    </div>
                    <span v-if="conn.is_cross_world" class="text-xs bg-primary/10 text-primary rounded-full px-2 py-0.5">
                        Cross-world
                    </span>
                </div>

                <p v-if="connections.length === 0" class="py-12 text-center text-muted-foreground">
                    No connections yet. Start pinging!
                </p>
            </div>
        </div>
    </EventLayout>
</template>
```

```vue
<!-- resources/js/pages/Event/Profile.vue -->
<script setup lang="ts">
import EventLayout from '@/layouts/EventLayout.vue'
import QrScanner from '@/components/qr/QrScanner.vue'
import { ref } from 'vue'

defineProps<{
    event: { id: number; name: string; slug: string }
    user: {
        id: number
        name: string
        email: string
        company?: string
        role_title?: string
        intent?: string
        participant_type: string
        status: string
        icebreaker_answer?: string
        notification_mode: string
        is_invisible: boolean
    }
    interestTags: string[]
}>()

const showScanner = ref(false)

function handleScan(result: string) {
    showScanner.value = false
    // Post the raw scan result to the QR resolve endpoint and render
    // a local error state instead of navigating directly to untrusted input.
}
</script>

<template>
    <EventLayout :event-name="event.name" :event-slug="event.slug">
        <div class="mx-auto max-w-2xl px-4 py-6">
            <h2 class="text-xl font-bold mb-4">Your Profile</h2>

            <div class="space-y-4">
                <div class="rounded-xl border p-4">
                    <h3 class="font-semibold text-lg">{{ user.name }}</h3>
                    <p v-if="user.company" class="text-muted-foreground">
                        {{ user.role_title ? `${user.role_title} at ` : '' }}{{ user.company }}
                    </p>
                    <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                    <p class="text-sm mt-1">
                        {{ user.participant_type === 'physical' ? '📍 Here in person' : '🌐 Joining remotely' }}
                    </p>
                </div>

                <div class="rounded-xl border p-4">
                    <h4 class="font-medium mb-2">Interest Tags</h4>
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="tag in interestTags"
                            :key="tag"
                            class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary"
                        >
                            {{ tag }}
                        </span>
                    </div>
                </div>

                <button
                    @click="showScanner = !showScanner"
                    class="w-full rounded-lg border p-3 text-center font-medium"
                >
                    {{ showScanner ? 'Close Scanner' : '📷 Scan QR Code' }}
                </button>

                <QrScanner v-if="showScanner" @scan="handleScan" />
            </div>
        </div>
    </EventLayout>
</template>
```

- [ ] **Step 4: Add routes**

Add to `routes/web.php`:

```php
use App\Http\Controllers\ConnectionListController;
use App\Http\Controllers\EventProfileController;

Route::get('/event/{event:slug}/connections', ConnectionListController::class)->name('event.connections');
Route::get('/event/{event:slug}/profile', EventProfileController::class)->name('event.profile');
```

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/ConnectionListController.php app/Http/Controllers/EventProfileController.php resources/js/pages/Event/Connections.vue resources/js/pages/Event/Profile.vue routes/web.php
git commit -m "feat: add connection list and profile pages for bottom tab navigation"
```

---

## Task 6: Run Full Suite & Lint

- [ ] **Step 1: Run all tests**

Run: `php artisan test --compact`
Expected: All tests PASS

- [ ] **Step 2: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 3: Build frontend**

Run: `npm run build`
Expected: Build succeeds

- [ ] **Step 4: Commit any fixes**

```bash
git add -A
git commit -m "style: apply formatting and verify build"
```
