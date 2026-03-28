# QR Instant Join — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let attendees scan a QR code at the event entrance, enter their name, and land directly in the onboarding wizard — no email or magic link required.

**Architecture:** New `QuickJoinController` creates a name-only user and logs them in. The existing onboarding wizard gets a new email collection step (step 4 of 5). The homepage is redesigned as a full-screen QR projector view.

**Tech Stack:** Laravel 13, Inertia v3, Vue 3, Pest 4, Tailwind v4

**Spec:** `docs/superpowers/specs/2026-03-28-qr-instant-join-design.md`

---

### Task 1: Make email nullable on users table

**Files:**
- Create: `database/migrations/XXXX_make_email_nullable_on_users_table.php`
- Test: `tests/Feature/Http/QuickJoinTest.php`

- [ ] **Step 1: Create migration**

```bash
php artisan make:migration make_email_nullable_on_users_table --no-interaction
```

- [ ] **Step 2: Write migration content**

```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('email')->nullable()->change();
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('email')->nullable(false)->change();
    });
}
```

- [ ] **Step 3: Run migration**

```bash
php artisan migrate
```

- [ ] **Step 4: Commit**

```bash
git add database/migrations/*make_email_nullable*
git commit -m "feat: make email nullable on users table for QR join flow"
```

---

### Task 2: QuickJoinController + routes (TDD)

**Files:**
- Create: `app/Http/Controllers/QuickJoinController.php`
- Create: `tests/Feature/Http/QuickJoinTest.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Create test file**

```bash
php artisan make:test Http/QuickJoinTest --pest --no-interaction
```

- [ ] **Step 2: Write failing tests**

```php
<?php

use App\Models\Event;
use App\Models\User;

it('shows the quick join form for an event', function () {
    $event = Event::factory()->live()->create();

    $this->get(route('event.join', $event))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Event/QuickJoin')
            ->where('event.slug', $event->slug)
        );
});

it('creates a user with name only and logs them in', function () {
    $event = Event::factory()->live()->create();

    $response = $this->post(route('event.join.store', $event), [
        'name' => 'Taylor Brooks',
    ]);

    $user = User::where('name', 'Taylor Brooks')->first();

    expect($user)->not->toBeNull()
        ->and($user->email)->toBeNull()
        ->and($user->password)->toBeNull();

    expect($user->events->pluck('id'))->toContain($event->id);

    $response->assertRedirect(route('event.onboarding.type', $event));

    $this->assertAuthenticatedAs($user);
});

it('validates name is required', function () {
    $event = Event::factory()->live()->create();

    $this->post(route('event.join.store', $event), ['name' => ''])
        ->assertSessionHasErrors('name');
});

it('redirects authenticated users who already joined to feed', function () {
    $event = Event::factory()->live()->create();
    $user = User::factory()->create();
    $event->participants()->attach($user, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);

    $this->actingAs($user)
        ->get(route('event.join', $event))
        ->assertRedirect(route('event.feed', $event));
});

it('attaches authenticated user without event and redirects to onboarding', function () {
    $event = Event::factory()->live()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('event.join', $event))
        ->assertRedirect(route('event.onboarding.type', $event));

    expect($user->events->pluck('id'))->toContain($event->id);
});
```

- [ ] **Step 3: Run tests to verify they fail**

```bash
php artisan test --compact --filter=QuickJoin
```

Expected: FAIL (routes and controller don't exist yet)

- [ ] **Step 4: Add routes to web.php**

Add these routes **before** the `auth` middleware group (the join page must be accessible without auth):

```php
Route::get('/event/{event:slug}/join', [QuickJoinController::class, 'show'])->name('event.join');
Route::post('/event/{event:slug}/join', [QuickJoinController::class, 'store'])->name('event.join.store');
```

Add the import at the top:
```php
use App\Http\Controllers\QuickJoinController;
```

- [ ] **Step 5: Create QuickJoinController**

```bash
php artisan make:controller QuickJoinController --no-interaction
```

```php
<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class QuickJoinController extends Controller
{
    public function show(Request $request, Event $event): Response|RedirectResponse
    {
        if ($request->user()) {
            if ($request->user()->events()->where('event_id', $event->id)->exists()) {
                return redirect()->route('event.feed', $event);
            }

            $request->user()->events()->attach($event->id, [
                'status' => 'available',
            ]);

            return redirect()->route('event.onboarding.type', $event);
        }

        return Inertia::render('Event/QuickJoin', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'slug' => $event->slug,
                'venue' => $event->venue,
            ],
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user = \App\Models\User::create([
            'name' => $validated['name'],
        ]);

        $user->events()->attach($event->id, [
            'status' => 'available',
        ]);

        Auth::login($user);

        return redirect()->route('event.onboarding.type', $event);
    }
}
```

- [ ] **Step 6: Run tests to verify they pass**

```bash
php artisan test --compact --filter=QuickJoin
```

Expected: all PASS

- [ ] **Step 7: Lint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/QuickJoinController.php tests/Feature/Http/QuickJoinTest.php routes/web.php
git commit -m "feat: add QuickJoinController for QR instant join"
```

---

### Task 3: QuickJoin.vue — "What's your name?" page

**Files:**
- Create: `resources/js/pages/Event/QuickJoin.vue`

- [ ] **Step 1: Create the Vue component**

```vue
<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    event: { id: number; name: string; slug: string; venue?: string };
}>();

const form = useForm({
    name: '',
});

function submit() {
    form.post(`/event/${props.event.slug}/join`);
}
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-white px-6">
        <Head :title="`${event.name} - Join`" />

        <div class="flex size-16 items-center justify-center rounded-2xl bg-orange-600 text-2xl font-bold text-white">
            {{ event.name.charAt(0) }}
        </div>

        <h2 class="mt-4 text-xl font-bold text-neutral-900">{{ event.name }}</h2>
        <p v-if="event.venue" class="text-sm text-neutral-500">{{ event.venue }}</p>

        <h1 class="mt-8 text-center text-2xl font-bold text-neutral-900">
            What's your name?
        </h1>
        <p class="mt-2 text-center text-sm text-neutral-500">
            Enter your name to join the event.
        </p>

        <form class="mt-6 w-full max-w-sm space-y-3" @submit.prevent="submit">
            <div>
                <input
                    v-model="form.name"
                    type="text"
                    placeholder="Your name"
                    autofocus
                    class="w-full rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-base outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-100"
                />
                <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">
                    {{ form.errors.name }}
                </p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-xl bg-orange-600 py-3 text-base font-semibold text-white transition hover:bg-orange-700 disabled:opacity-50"
            >
                Join Event
            </button>
        </form>
    </div>
</template>
```

- [ ] **Step 2: Build and verify**

```bash
npm run build
```

Expected: build succeeds

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/Event/QuickJoin.vue
git commit -m "feat: add QuickJoin.vue name-only join page"
```

---

### Task 4: Email collection onboarding step (TDD)

**Files:**
- Create: `resources/js/pages/Event/Onboarding/EmailCollection.vue`
- Modify: `app/Http/Controllers/OnboardingController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Http/OnboardingEmailTest.php`

- [ ] **Step 1: Create test file**

```bash
php artisan make:test Http/OnboardingEmailTest --pest --no-interaction
```

- [ ] **Step 2: Write failing tests**

```php
<?php

use App\Models\Event;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->user = User::factory()->create(['email' => null]);
    $this->event->participants()->attach($this->user, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
});

it('shows the email collection step', function () {
    $this->actingAs($this->user)
        ->get(route('event.onboarding.email', $this->event))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Event/Onboarding/EmailCollection')
            ->where('event.slug', $this->event->slug)
            ->where('currentEmail', null)
        );
});

it('saves email to user record', function () {
    $this->actingAs($this->user)
        ->post(route('event.onboarding.email.save', $this->event), [
            'email' => 'taylor@example.com',
        ])
        ->assertRedirect(route('event.onboarding.ready', $this->event));

    expect($this->user->fresh()->email)->toBe('taylor@example.com');
});

it('allows skipping email by submitting empty', function () {
    $this->actingAs($this->user)
        ->post(route('event.onboarding.email.save', $this->event), [
            'email' => '',
        ])
        ->assertRedirect(route('event.onboarding.ready', $this->event));

    expect($this->user->fresh()->email)->toBeNull();
});

it('rejects duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($this->user)
        ->post(route('event.onboarding.email.save', $this->event), [
            'email' => 'taken@example.com',
        ])
        ->assertSessionHasErrors('email');
});

it('shows existing email if user already has one', function () {
    $this->user->update(['email' => 'existing@example.com']);

    $this->actingAs($this->user)
        ->get(route('event.onboarding.email', $this->event))
        ->assertInertia(fn ($page) => $page
            ->where('currentEmail', 'existing@example.com')
        );
});
```

- [ ] **Step 3: Run tests to verify they fail**

```bash
php artisan test --compact --filter=OnboardingEmail
```

Expected: FAIL

- [ ] **Step 4: Add routes to web.php**

Inside the `auth` middleware group, after the icebreaker routes:

```php
Route::get('/event/{event:slug}/onboarding/email', [OnboardingController::class, 'email'])->name('event.onboarding.email');
Route::post('/event/{event:slug}/onboarding/email', [OnboardingController::class, 'saveEmail'])->name('event.onboarding.email.save');
```

- [ ] **Step 5: Add email methods to OnboardingController**

Add after `saveIcebreaker()`:

```php
public function email(Request $request, Event $event): Response
{
    return Inertia::render('Event/Onboarding/EmailCollection', [
        'event' => $this->eventProps($event),
        'currentEmail' => $request->user()->email,
    ]);
}

public function saveEmail(Request $request, Event $event): RedirectResponse
{
    $validated = $request->validate([
        'email' => ['nullable', 'email', 'max:255', 'unique:users,email,'.$request->user()->id],
    ]);

    if ($validated['email']) {
        $request->user()->update(['email' => $validated['email']]);
    }

    return redirect()->route('event.onboarding.ready', $event);
}
```

- [ ] **Step 6: Update `saveIcebreaker` redirect to go to email step instead of ready**

In `OnboardingController::saveIcebreaker()`, change:

```php
return redirect()->route('event.onboarding.ready', $event);
```

to:

```php
return redirect()->route('event.onboarding.email', $event);
```

- [ ] **Step 7: Run tests to verify they pass**

```bash
php artisan test --compact --filter=OnboardingEmail
```

Expected: all PASS

- [ ] **Step 8: Create EmailCollection.vue**

```vue
<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import StepProgress from '@/components/onboarding/StepProgress.vue';

const props = defineProps<{
    event: { id: number; name: string; slug: string };
    currentEmail: string | null;
}>();

const form = useForm({
    email: props.currentEmail ?? '',
});

function submit() {
    form.post(`/event/${props.event.slug}/onboarding/email`);
}

function skip() {
    router.visit(`/event/${props.event.slug}/onboarding/ready`);
}
</script>

<template>
    <div class="flex h-fit flex-col bg-white px-6 pt-6 pb-8">
        <Head :title="`${event.name} - Email`" />

        <StepProgress :current-step="4" :total-steps="5" />

        <div class="mt-8">
            <h1 class="text-2xl font-bold text-neutral-900">Stay connected</h1>
            <p class="mt-1 text-sm text-neutral-500">
                Add your email so people can reach you after the event.
            </p>
        </div>

        <form class="mt-6 space-y-3" @submit.prevent="submit">
            <div>
                <input
                    v-model="form.email"
                    type="email"
                    placeholder="your@email.com"
                    autofocus
                    class="w-full rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-base outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-100"
                />
                <p v-if="form.errors.email" class="mt-1 text-sm text-red-500">
                    {{ form.errors.email }}
                </p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-xl bg-orange-600 py-3.5 text-base font-semibold text-white transition hover:bg-orange-700 disabled:opacity-40"
            >
                Continue
            </button>
        </form>

        <div class="mt-auto flex items-center gap-3 pt-6">
            <button
                class="w-full px-6 py-3.5 text-sm font-medium text-neutral-500 transition hover:text-neutral-700"
                @click="skip"
            >
                Skip
            </button>
        </div>
    </div>
</template>
```

- [ ] **Step 9: Lint, build, and commit**

```bash
vendor/bin/pint --dirty --format agent
npm run build
git add app/Http/Controllers/OnboardingController.php routes/web.php resources/js/pages/Event/Onboarding/EmailCollection.vue tests/Feature/Http/OnboardingEmailTest.php
git commit -m "feat: add email collection onboarding step"
```

---

### Task 5: Update step progress across all onboarding pages

**Files:**
- Modify: `resources/js/pages/Event/Onboarding/TypeSelection.vue`
- Modify: `resources/js/pages/Event/Onboarding/InterestTags.vue`
- Modify: `resources/js/pages/Event/Onboarding/IcebreakerSelection.vue`
- Modify: `resources/js/pages/Event/Onboarding/ReadyScreen.vue`

- [ ] **Step 1: Update total-steps from 4 to 5 in all four files**

`TypeSelection.vue`: change `:current-step="1" :total-steps="4"` → `:current-step="1" :total-steps="5"`

`InterestTags.vue`: change `:current-step="2" :total-steps="4"` → `:current-step="2" :total-steps="5"`

`IcebreakerSelection.vue`: change `:current-step="3" :total-steps="4"` → `:current-step="3" :total-steps="5"`

`ReadyScreen.vue`: change `:current-step="4" :total-steps="4"` → `:current-step="5" :total-steps="5"`

- [ ] **Step 2: Build and verify**

```bash
npm run build
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/Event/Onboarding/TypeSelection.vue resources/js/pages/Event/Onboarding/InterestTags.vue resources/js/pages/Event/Onboarding/IcebreakerSelection.vue resources/js/pages/Event/Onboarding/ReadyScreen.vue
git commit -m "feat: update onboarding step progress to 5 steps"
```

---

### Task 6: Redesign homepage as QR projector view

**Files:**
- Modify: `app/Http/Controllers/EventLandingController.php`
- Modify: `resources/js/pages/JoinEvent.vue`

- [ ] **Step 1: Update EventLandingController**

Replace the `__invoke` method:

```php
public function __invoke(Request $request): Response|RedirectResponse
{
    if ($request->user()) {
        $user = $request->user();
        $event = $user->events()->latest('event_user.created_at')->first()
            ?? $user->organizedEvents()->latest()->first();

        if (! $event) {
            return Inertia::render('JoinEvent', ['event' => null, 'joinUrl' => null]);
        }

        if ($user->id === $event->organizer_id) {
            return redirect()->route('event.dashboard', $event);
        }

        return redirect()->route('event.feed', $event);
    }

    $event = Event::first();

    return Inertia::render('JoinEvent', [
        'event' => $event ? [
            'name' => $event->name,
            'slug' => $event->slug,
            'venue' => $event->venue,
            'starts_at' => $event->starts_at?->toISOString(),
            'ends_at' => $event->ends_at?->toISOString(),
        ] : null,
        'joinUrl' => $event ? route('event.join', $event) : null,
    ]);
}
```

- [ ] **Step 2: Redesign JoinEvent.vue as QR projector**

```vue
<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted } from 'vue';
import QRCode from 'qrcode';
import { ref } from 'vue';

const props = defineProps<{
    event: {
        name: string;
        slug: string;
        starts_at: string;
        ends_at: string;
        venue: string;
    } | null;
    joinUrl: string | null;
}>();

const qrDataUrl = ref<string | null>(null);

onMounted(async () => {
    document.documentElement.classList.remove('dark');
    if (props.joinUrl) {
        qrDataUrl.value = await QRCode.toDataURL(props.joinUrl, {
            width: 280,
            margin: 2,
            color: { dark: '#171717', light: '#ffffff' },
        });
    }
});

onUnmounted(() => {
    // Theme restored by next page
});

const formattedDate = computed(() => {
    if (!props.event) return '';
    const start = new Date(props.event.starts_at);
    const end = new Date(props.event.ends_at);
    const dateOpts: Intl.DateTimeFormatOptions = { month: 'short', day: 'numeric' };
    const timeOpts: Intl.DateTimeFormatOptions = { hour: '2-digit', minute: '2-digit' };
    return `${start.toLocaleDateString(undefined, dateOpts)} · ${start.toLocaleTimeString(undefined, timeOpts)}–${end.toLocaleTimeString(undefined, timeOpts)}`;
});
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-white px-6">
        <Head :title="event?.name ?? 'Join Event'" />

        <template v-if="event">
            <div class="flex size-20 items-center justify-center rounded-2xl bg-orange-600 text-3xl font-bold text-white">
                {{ event.name.charAt(0) }}
            </div>

            <h1 class="mt-6 text-center text-3xl font-bold text-neutral-900">{{ event.name }}</h1>
            <p class="mt-1 text-sm text-neutral-500">
                {{ formattedDate }}<template v-if="event.venue"> · {{ event.venue }}</template>
            </p>

            <p class="mt-8 text-center text-lg font-medium text-neutral-700">
                Scan to join
            </p>

            <div class="mt-4 rounded-2xl border-2 border-neutral-100 p-4">
                <img
                    v-if="qrDataUrl"
                    :src="qrDataUrl"
                    alt="Scan to join event"
                    class="size-[280px]"
                />
                <div v-else class="flex size-[280px] items-center justify-center text-sm text-neutral-400">
                    Loading...
                </div>
            </div>

            <a
                :href="`/event/${event.slug}/join`"
                class="mt-8 text-sm font-medium text-orange-600 transition hover:text-orange-700"
            >
                Joining remotely? Tap here →
            </a>
        </template>

        <template v-else>
            <p class="text-neutral-500">No event is currently active.</p>
        </template>
    </div>
</template>
```

- [ ] **Step 3: Install qrcode npm package**

```bash
npm install qrcode && npm install -D @types/qrcode
```

- [ ] **Step 4: Build and verify**

```bash
npm run build
```

- [ ] **Step 5: Run existing EventLanding tests**

```bash
php artisan test --compact --filter=EventLanding
```

Expected: PASS (update tests if joinUrl assertion is needed)

- [ ] **Step 6: Lint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/EventLandingController.php resources/js/pages/JoinEvent.vue package.json package-lock.json
git commit -m "feat: redesign homepage as QR projector for event join"
```

---

### Task 7: Regenerate Wayfinder routes and final verification

- [ ] **Step 1: Regenerate Wayfinder**

```bash
php artisan wayfinder:generate
```

- [ ] **Step 2: Build frontend**

```bash
npm run build
```

- [ ] **Step 3: Run full test suite**

```bash
php artisan test --compact
```

Expected: all PASS

- [ ] **Step 4: Commit if Wayfinder generated new files**

```bash
git add resources/js/actions resources/js/routes
git commit -m "chore: regenerate Wayfinder routes for quick join"
```
