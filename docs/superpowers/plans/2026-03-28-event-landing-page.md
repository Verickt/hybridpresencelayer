# Event Landing Page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the generic Welcome page with an event landing page that lets unauthenticated visitors join the event via magic link, and redirects authenticated users to their feed/dashboard.

**Architecture:** New `EventLandingController` handles `GET /`. Unauthenticated users see a `JoinEvent.vue` page (matching Paper screen 01) with event info and an email form that POSTs to the existing `/magic-link` endpoint. Authenticated users are redirected based on role. The old `Welcome.vue` page and the separate `/dashboard` redirect route are removed.

**Tech Stack:** Laravel 13, Inertia v3 + Vue 3, Pest 4, Tailwind v4

---

### Task 1: Create EventLandingController with Tests

**Files:**
- Create: `app/Http/Controllers/EventLandingController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Http/EventLandingTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/Http/EventLandingTest.php`:

```php
<?php

use App\Models\Event;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('unauthenticated visitor sees join event page', function () {
    $event = Event::factory()->live()->create([
        'name' => 'TechConf 2026',
        'venue' => 'Berlin',
        'allow_open_registration' => true,
    ]);

    $response = $this->get('/');

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('JoinEvent')
            ->where('event.name', 'TechConf 2026')
            ->where('event.venue', 'Berlin')
            ->where('event.slug', $event->slug)
            ->has('event.starts_at')
            ->has('event.ends_at')
        );
});

test('unauthenticated visitor sees empty state when no event exists', function () {
    $response = $this->get('/');

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('JoinEvent')
            ->where('event', null)
        );
});

test('authenticated participant is redirected to event feed', function () {
    $event = Event::factory()->live()->create();
    $user = User::factory()->create();
    $event->participants()->attach($user, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);

    $response = $this->actingAs($user)->get('/');

    $response->assertRedirect(route('event.feed', $event));
});

test('authenticated organizer is redirected to event dashboard', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->live()->create(['organizer_id' => $organizer->id]);

    $response = $this->actingAs($organizer)->get('/');

    $response->assertRedirect(route('event.dashboard', $event));
});

test('authenticated user with no event sees join page', function () {
    $event = Event::factory()->live()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('JoinEvent')
        );
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=EventLandingTest`
Expected: FAIL — controller doesn't exist yet

- [ ] **Step 3: Create the controller**

Run: `php artisan make:class App/Http/Controllers/EventLandingController --no-interaction`

Then replace its content with:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class EventLandingController extends Controller
{
    public function __invoke(Request $request): Response|HttpResponse
    {
        $event = Event::first();
        $user = $request->user();

        if ($user && $event) {
            $isParticipant = $event->participants()->where('user_id', $user->id)->exists();
            $isOrganizer = $user->id === $event->organizer_id;

            if ($isOrganizer) {
                return redirect()->route('event.dashboard', $event);
            }

            if ($isParticipant) {
                return redirect()->route('event.feed', $event);
            }
        }

        return Inertia::render('JoinEvent', [
            'event' => $event ? [
                'name' => $event->name,
                'slug' => $event->slug,
                'venue' => $event->venue,
                'starts_at' => $event->starts_at->toISOString(),
                'ends_at' => $event->ends_at->toISOString(),
            ] : null,
        ]);
    }
}
```

- [ ] **Step 4: Update routes**

In `routes/web.php`, replace:

```php
Route::inertia('/', 'Welcome', [
    'canRegister' => false,
])->name('home');
```

With:

```php
Route::get('/', EventLandingController::class)->name('home');
```

Add the import at the top of the file:

```php
use App\Http\Controllers\EventLandingController;
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --compact --filter=EventLandingTest`
Expected: All 5 tests PASS

- [ ] **Step 6: Lint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/EventLandingController.php tests/Feature/Http/EventLandingTest.php routes/web.php
git commit -m "feat: add EventLandingController with role-based redirect"
```

---

### Task 2: Create JoinEvent.vue Page

**Files:**
- Create: `resources/js/pages/JoinEvent.vue`
- Delete: `resources/js/pages/Welcome.vue`

- [ ] **Step 1: Create `resources/js/pages/JoinEvent.vue`**

This page matches Paper screen 01 — "Magic Link Entry". It shows the event info, email input, optional name input (for open registration), and a "Send Magic Link" button. Posts to the existing `/magic-link` endpoint.

```vue
<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import InputError from '@/components/InputError.vue';
import { send } from '@/actions/Auth/MagicLinkController';
import { ref, computed } from 'vue';
import { useHttp } from '@inertiajs/vue3';

const props = defineProps<{
    event: {
        name: string;
        slug: string;
        venue: string | null;
        starts_at: string;
        ends_at: string;
    } | null;
}>();

const name = ref('');
const email = ref('');
const sent = ref(false);
const errors = ref<Record<string, string>>({});

const http = useHttp();

const dateRange = computed(() => {
    if (!props.event) return '';
    const start = new Date(props.event.starts_at);
    const end = new Date(props.event.ends_at);
    const opts: Intl.DateTimeFormatOptions = { month: 'long', day: 'numeric' };
    const startStr = start.toLocaleDateString('en-US', opts);
    const endStr = end.toLocaleDateString('en-US', { ...opts, year: 'numeric' });
    return `${startStr}–${endStr}`;
});

const initial = computed(() => {
    if (!props.event) return '?';
    return props.event.name.charAt(0).toUpperCase();
});

function submit() {
    if (!props.event) return;

    errors.value = {};

    http.post(send.url(), {
        name: name.value,
        email: email.value,
        event_slug: props.event.slug,
    }, {
        onSuccess: () => {
            sent.value = true;
        },
        onError: (errs: Record<string, string>) => {
            errors.value = errs;
        },
    });
}
</script>

<template>
    <Head :title="event?.name ?? 'Join Event'" />

    <div class="flex min-h-screen flex-col items-center justify-center bg-white px-6 py-12">
        <!-- No event state -->
        <div v-if="!event" class="text-center">
            <p class="text-lg text-gray-500">No event is currently running.</p>
        </div>

        <!-- Event join form -->
        <div v-else class="w-full max-w-[340px]">
            <!-- Event identity -->
            <div class="mb-8 flex flex-col items-center text-center">
                <div
                    class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-500 text-2xl font-semibold text-white"
                >
                    {{ initial }}
                </div>
                <h1 class="text-xl font-semibold text-gray-900">{{ event.name }}</h1>
                <p class="text-sm text-gray-500">
                    {{ dateRange }}<span v-if="event.venue"> · {{ event.venue }}</span>
                </p>
            </div>

            <!-- Success state -->
            <div v-if="sent" class="text-center">
                <h2 class="mb-2 text-xl font-semibold text-gray-900">Check your email</h2>
                <p class="text-sm text-gray-500">
                    We sent a magic link to <strong>{{ email }}</strong>. Click it to join the event.
                </p>
            </div>

            <!-- Form -->
            <div v-else>
                <h2 class="mb-1 text-center text-2xl font-bold text-gray-900">Join the conversation</h2>
                <p class="mb-6 text-center text-sm text-gray-500">
                    Enter your email to get a magic link. No password needed.
                </p>

                <form class="flex flex-col gap-4" @submit.prevent="submit">
                    <div class="grid gap-1.5">
                        <Label for="name">Your name</Label>
                        <Input
                            id="name"
                            v-model="name"
                            type="text"
                            placeholder="Jane Doe"
                            required
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            v-model="email"
                            type="email"
                            placeholder="your@email.com"
                            required
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <Button
                        type="submit"
                        class="mt-2 w-full"
                        :disabled="http.processing.value"
                    >
                        <Spinner v-if="http.processing.value" />
                        Send Magic Link
                    </Button>
                </form>

                <div class="mt-6 border-t border-gray-100 pt-4 text-center">
                    <p class="text-xs text-gray-400">or scan your invitation QR code</p>
                </div>
            </div>
        </div>
    </div>
</template>
```

- [ ] **Step 2: Delete `resources/js/pages/Welcome.vue`**

```bash
rm resources/js/pages/Welcome.vue
```

- [ ] **Step 3: Remove the `/dashboard` redirect route**

In `routes/web.php`, remove this entire block (lines 45-62):

```php
Route::middleware(['auth'])->group(function () {
    // Redirect /dashboard to the user's event — participants should never see a generic dashboard
    Route::get('dashboard', function () {
        $user = auth()->user();
        $event = $user->events()->latest('event_user.created_at')->first()
            ?? $user->organizedEvents()->latest()->first();

        if (! $event) {
            return redirect('/');
        }

        if ($user->id === $event->organizer_id) {
            return redirect()->route('event.dashboard', $event);
        }

        return redirect()->route('event.feed', $event);
    })->name('dashboard');
});
```

The `EventLandingController` now handles this logic at `/`.

- [ ] **Step 4: Regenerate Wayfinder routes**

Run: `php artisan wayfinder:generate`

- [ ] **Step 5: Build frontend**

Run: `npm run build`

- [ ] **Step 6: Fix any test breakage from removing Welcome/dashboard route**

Run: `php artisan test --compact`

Check for tests referencing `Welcome` component or `dashboard` route name. Update any that break:
- Tests using `route('dashboard')` should use `route('home')` or `/` instead
- Tests asserting `Welcome` component should assert `JoinEvent` instead

- [ ] **Step 7: Lint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "feat: replace Welcome with JoinEvent page, remove /dashboard redirect"
```

---

### Task 3: Browser Verification

**Files:** None (verification only)

- [ ] **Step 1: Visit `/` in browser while logged out**

Use `mcp__claude-in-chrome__*` tools to navigate to the app URL. Verify:
- Event name, date range, and venue are displayed
- Name and email inputs are visible
- "Send Magic Link" button is visible
- "or scan your invitation QR code" footer text appears

- [ ] **Step 2: Submit the form with a test email**

Fill in a name and email, submit. Verify:
- Success message appears: "Check your email"
- No console errors

- [ ] **Step 3: Visit `/` while logged in as a participant**

Verify redirect to `/event/{slug}/feed`.

- [ ] **Step 4: Visit `/` while logged in as an organizer**

Verify redirect to `/event/{slug}/dashboard`.
