# Plan 7: Organizer Dashboard — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the organizer dashboard with real-time KPIs, event setup wizard, session/booth analytics, and organizer actions (boost booth, trigger serendipity wave, adjust matching weights).

**Architecture:** `DashboardController` serves cached KPIs (30s cache) from `DashboardService`. `EventSetupController` handles the 6-step wizard with DB transactions for imports. Organizer actions modify matching weights and trigger suggestions via existing services. Access is restricted via `organizer_id` check on Event (MVP — use Policy in production). All routes use scoped bindings. Serendipity waves exclude busy/DND/already-connected users.

**Tech Stack:** Laravel 13, Inertia v3, Vue 3, Pest v4

**Depends on:** Plan 1 (models), Plan 3 (discovery), Plan 5 (sessions), Plan 6 (booths)

## TDD Standard

- Start each task with failing tests before implementation.
- Cover negative and edge cases: unauthorized organizers, empty-state metrics, cache invalidation, malformed imports, duplicate attendee rows, and organizer action throttling.
- For dashboard and wizard endpoints, add `assertInertia` coverage for KPI props, flash messages, and hidden/sensitive fields.
- Add browser smoke coverage plus real browser tests for one successful organizer flow and one rejection/failure flow.

---

## Task 1: Dashboard KPIs Service

**Files:**
- Create: `app/Services/DashboardService.php`
- Create: `tests/Feature/Services/DashboardServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Services/DashboardServiceTest.php
<?php

use App\Models\Connection;
use App\Models\Event;
use App\Models\Ping;
use App\Models\User;
use App\Services\DashboardService;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->service = app(DashboardService::class);
});

it('counts active participants by type', function () {
    User::factory(5)->create()->each(fn ($u) =>
        $this->event->participants()->attach($u, ['participant_type' => 'physical', 'status' => 'available'])
    );
    User::factory(3)->create()->each(fn ($u) =>
        $this->event->participants()->attach($u, ['participant_type' => 'remote', 'status' => 'available'])
    );

    $stats = $this->service->overview($this->event);

    expect($stats['total_active'])->toBe(8)
        ->and($stats['physical_count'])->toBe(5)
        ->and($stats['remote_count'])->toBe(3);
});

it('calculates cross-pollination rate', function () {
    Connection::factory(3)->create(['event_id' => $this->event->id, 'is_cross_world' => true]);
    Connection::factory(7)->create(['event_id' => $this->event->id, 'is_cross_world' => false]);

    $stats = $this->service->overview($this->event);

    expect($stats['cross_pollination_rate'])->toBe(30.0);
});

it('calculates interaction rate', function () {
    $users = User::factory(10)->create();
    foreach ($users as $user) {
        $this->event->participants()->attach($user, ['participant_type' => 'physical', 'status' => 'available']);
    }

    // 4 users have sent or received pings
    Ping::factory()->create(['sender_id' => $users[0]->id, 'event_id' => $this->event->id]);
    Ping::factory()->create(['sender_id' => $users[1]->id, 'event_id' => $this->event->id]);
    Ping::factory()->create(['receiver_id' => $users[2]->id, 'sender_id' => $users[3]->id, 'event_id' => $this->event->id]);

    $stats = $this->service->overview($this->event);

    expect($stats['interaction_rate'])->toBe(40.0);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=DashboardServiceTest`
Expected: FAIL

- [ ] **Step 3: Create DashboardService**

```php
// app/Services/DashboardService.php
<?php

namespace App\Services;

use App\Models\Connection;
use App\Models\Event;
use App\Models\Ping;
use App\Models\Suggestion;

class DashboardService
{
    public function overview(Event $event): array
    {
        return \Illuminate\Support\Facades\Cache::remember(
            "event.{$event->id}.dashboard.overview",
            30, // 30 seconds — avoid expensive recomputation on every load
            fn () => $this->computeOverview($event)
        );
    }

    private function computeOverview(Event $event): array
    {
        $participants = $event->participants()->get();
        $physicalCount = $participants->filter(fn ($p) => $p->pivot->participant_type === 'physical')->count();
        $remoteCount = $participants->filter(fn ($p) => $p->pivot->participant_type === 'remote')->count();
        $totalActive = $physicalCount + $remoteCount;

        $totalConnections = Connection::where('event_id', $event->id)->count();
        $crossWorldConnections = Connection::where('event_id', $event->id)->where('is_cross_world', true)->count();
        $crossPollinationRate = $totalConnections > 0
            ? round(($crossWorldConnections / $totalConnections) * 100, 1)
            : 0.0;

        $interactedUserIds = Ping::where('event_id', $event->id)
            ->pluck('sender_id')
            ->merge(Ping::where('event_id', $event->id)->pluck('receiver_id'))
            ->unique();
        $interactionRate = $totalActive > 0
            ? round(($interactedUserIds->count() / $totalActive) * 100, 1)
            : 0.0;

        $totalSuggestions = Suggestion::where('event_id', $event->id)->count();
        $acceptedSuggestions = Suggestion::where('event_id', $event->id)->where('status', 'accepted')->count();
        $matchAcceptanceRate = $totalSuggestions > 0
            ? round(($acceptedSuggestions / $totalSuggestions) * 100, 1)
            : 0.0;

        $networkingDensity = $totalActive > 1
            ? round((2 * $totalConnections) / ($totalActive * ($totalActive - 1)) * 100, 2)
            : 0.0;

        return [
            'total_active' => $totalActive,
            'physical_count' => $physicalCount,
            'remote_count' => $remoteCount,
            'total_connections' => $totalConnections,
            'cross_pollination_rate' => $crossPollinationRate,
            'interaction_rate' => $interactionRate,
            'match_acceptance_rate' => $matchAcceptanceRate,
            'networking_density' => $networkingDensity,
        ];
    }

    public function sessionAnalytics(Event $event): array
    {
        return $event->sessions()
            ->withCount(['checkIns', 'reactions', 'questions'])
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'title' => $s->title,
                'check_ins_count' => $s->check_ins_count,
                'reactions_count' => $s->reactions_count,
                'questions_count' => $s->questions_count,
            ])
            ->toArray();
    }

    public function boothPerformance(Event $event): array
    {
        return $event->booths()
            ->withCount(['visits' => fn ($q) => $q->where('is_anonymous', false)])
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'company' => $b->company,
                'visitor_count' => $b->visits_count,
            ])
            ->sortByDesc('visitor_count')
            ->values()
            ->toArray();
    }
}
```

- [ ] **Step 4: Run tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=DashboardServiceTest`
Expected: All 3 tests PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/DashboardService.php tests/Feature/Services/DashboardServiceTest.php
git commit -m "feat: add DashboardService with KPI calculations"
```

---

## Task 2: Dashboard Controller

**Files:**
- Create: `app/Http/Controllers/DashboardController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Http/DashboardControllerTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Http/DashboardControllerTest.php
<?php

use App\Models\Event;
use App\Models\User;

beforeEach(function () {
    $this->organizer = User::factory()->organizer()->create();
    $this->event = Event::factory()->live()->create(['organizer_id' => $this->organizer->id]);
});

it('shows dashboard for organizer', function () {
    $response = $this->actingAs($this->organizer)
        ->get(route('event.dashboard', $this->event));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Event/Dashboard')
            ->has('overview')
            ->has('sessionAnalytics')
            ->has('boothPerformance')
        );
});

it('rejects non-organizers', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('event.dashboard', $this->event));

    $response->assertForbidden();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=DashboardControllerTest`
Expected: FAIL

- [ ] **Step 3: Create DashboardController**

Run: `php artisan make:controller DashboardController --no-interaction`

```php
// app/Http/Controllers/DashboardController.php
<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, Event $event, DashboardService $dashboardService): Response
    {
        abort_unless($request->user()->id === $event->organizer_id, 403);

        return Inertia::render('Event/Dashboard', [
            'event' => ['id' => $event->id, 'name' => $event->name, 'slug' => $event->slug],
            'overview' => $dashboardService->overview($event),
            'sessionAnalytics' => $dashboardService->sessionAnalytics($event),
            'boothPerformance' => $dashboardService->boothPerformance($event),
        ]);
    }
}
```

- [ ] **Step 4: Add route**

Add to `routes/web.php`:

```php
use App\Http\Controllers\DashboardController;

Route::get('/event/{event:slug}/dashboard', DashboardController::class)->name('event.dashboard');
```

- [ ] **Step 5: Run tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=DashboardControllerTest`
Expected: All 2 tests PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/DashboardController.php routes/web.php tests/Feature/Http/DashboardControllerTest.php
git commit -m "feat: add organizer dashboard controller with KPIs and analytics"
```

---

## Task 3: Event Setup Controller (6-step wizard)

**Files:**
- Create: `app/Http/Controllers/EventSetupController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Http/EventSetupControllerTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Http/EventSetupControllerTest.php
<?php

use App\Models\Event;
use App\Models\User;

beforeEach(function () {
    $this->organizer = User::factory()->organizer()->create();
});

it('creates an event with basic details', function () {
    $response = $this->actingAs($this->organizer)
        ->post(route('events.store'), [
            'name' => 'BSI Conference 2026',
            'description' => 'Cyber security conference',
            'venue' => 'Congress Center Basel',
            'starts_at' => now()->addDays(7)->toISOString(),
            'ends_at' => now()->addDays(7)->addHours(8)->toISOString(),
            'allow_open_registration' => true,
        ]);

    $response->assertRedirect();
    expect(Event::where('name', 'BSI Conference 2026')->exists())->toBeTrue();
});

it('imports attendees via CSV-like payload', function () {
    $event = Event::factory()->create(['organizer_id' => $this->organizer->id]);

    $response = $this->actingAs($this->organizer)
        ->post(route('events.import-attendees', $event), [
            'attendees' => [
                ['name' => 'Alice', 'email' => 'alice@test.com', 'participant_type' => 'physical'],
                ['name' => 'Bob', 'email' => 'bob@test.com', 'participant_type' => 'remote'],
            ],
        ]);

    $response->assertOk();
    expect($event->participants()->count())->toBe(2);
});

it('updates matching weights', function () {
    $event = Event::factory()->create(['organizer_id' => $this->organizer->id]);

    $response = $this->actingAs($this->organizer)
        ->patch(route('events.update', $event), [
            'name' => $event->name,
            'starts_at' => $event->starts_at->toISOString(),
            'ends_at' => $event->ends_at->toISOString(),
        ]);

    $response->assertRedirect();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=EventSetupControllerTest`
Expected: FAIL

- [ ] **Step 3: Create EventSetupController**

Run: `php artisan make:controller EventSetupController --no-interaction`

```php
// app/Http/Controllers/EventSetupController.php
<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventSetupController extends Controller
{
    public function store(Request $request)
    {
        abort_unless($request->user()->is_organizer, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'venue' => ['nullable', 'string'],
            'streaming_url' => ['nullable', 'url'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'allow_open_registration' => ['boolean'],
        ]);

        $event = Event::create([
            ...$validated,
            'organizer_id' => $request->user()->id,
        ]);

        return redirect()->route('event.dashboard', $event);
    }

    public function update(Request $request, Event $event)
    {
        abort_unless($request->user()->id === $event->organizer_id, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'venue' => ['nullable', 'string'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'allow_open_registration' => ['boolean'],
        ]);

        $event->update($validated);

        return redirect()->route('event.dashboard', $event);
    }

    public function importAttendees(Request $request, Event $event): JsonResponse
    {
        abort_unless($request->user()->id === $event->organizer_id, 403);

        $validated = $request->validate([
            'attendees' => ['required', 'array'],
            'attendees.*.name' => ['required', 'string'],
            'attendees.*.email' => ['required', 'email'],
            'attendees.*.participant_type' => ['required', 'in:physical,remote'],
            'attendees.*.company' => ['nullable', 'string'],
            'attendees.*.role_title' => ['nullable', 'string'],
        ]);

        foreach ($validated['attendees'] as $attendee) {
            $user = User::firstOrCreate(
                ['email' => $attendee['email']],
                [
                    'name' => $attendee['name'],
                    'company' => $attendee['company'] ?? null,
                    'role_title' => $attendee['role_title'] ?? null,
                    'password' => bcrypt(str()->random(32)),
                ]
            );

            if (! $event->participants()->where('user_id', $user->id)->exists()) {
                $event->participants()->attach($user, [
                    'participant_type' => $attendee['participant_type'],
                    'status' => 'available',
                ]);
            }
        }

        return response()->json(['message' => 'Attendees imported', 'count' => count($validated['attendees'])]);
    }
}
```

- [ ] **Step 4: Add routes**

Add to `routes/web.php`:

```php
use App\Http\Controllers\EventSetupController;

Route::middleware(['auth'])->group(function () {
    Route::post('/events', [EventSetupController::class, 'store'])->name('events.store');
    Route::patch('/events/{event:slug}', [EventSetupController::class, 'update'])->name('events.update');
    Route::post('/events/{event:slug}/import-attendees', [EventSetupController::class, 'importAttendees'])->name('events.import-attendees');
});
```

- [ ] **Step 5: Run tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=EventSetupControllerTest`
Expected: All 3 tests PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/EventSetupController.php routes/web.php tests/Feature/Http/EventSetupControllerTest.php
git commit -m "feat: add event setup controller with create, update, and attendee import"
```

---

## Task 4: Organizer Actions Controller

**Files:**
- Create: `app/Http/Controllers/OrganizerActionController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Http/OrganizerActionControllerTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Http/OrganizerActionControllerTest.php
<?php

use App\Models\Event;
use App\Models\User;

beforeEach(function () {
    $this->organizer = User::factory()->organizer()->create();
    $this->event = Event::factory()->live()->create(['organizer_id' => $this->organizer->id]);
});

it('sends an event-wide announcement', function () {
    $response = $this->actingAs($this->organizer)
        ->post(route('event.actions.announce', $this->event), [
            'message' => 'Networking hour starts now!',
        ]);

    $response->assertOk();
});

it('triggers a serendipity wave', function () {
    // Add some participants
    $users = User::factory(6)->create();
    foreach ($users as $user) {
        $this->event->participants()->attach($user, ['participant_type' => 'physical', 'status' => 'available']);
    }

    $response = $this->actingAs($this->organizer)
        ->post(route('event.actions.serendipity-wave', $this->event));

    $response->assertOk();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=OrganizerActionControllerTest`
Expected: FAIL

- [ ] **Step 3: Create OrganizerActionController**

Run: `php artisan make:controller OrganizerActionController --no-interaction`

```php
// app/Http/Controllers/OrganizerActionController.php
<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\SuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event as EventBus;

class OrganizerActionController extends Controller
{
    public function announce(Request $request, Event $event): JsonResponse
    {
        abort_unless($request->user()->id === $event->organizer_id, 403);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        // Broadcast to all event participants
        \App\Events\ParticipantStatusChanged::dispatch(
            $event,
            $request->user(),
            'available',
            "📢 {$validated['message']}"
        );

        return response()->json(['message' => 'Announcement sent']);
    }

    public function serendipityWave(Request $request, Event $event, SuggestionService $suggestionService): JsonResponse
    {
        abort_unless($request->user()->id === $event->organizer_id, 403);

        $participants = $event->participants()
            ->wherePivot('status', '!=', 'busy')
            ->get();

        $generated = 0;
        foreach ($participants as $participant) {
            $suggestions = $suggestionService->generateForUser($participant, $event);
            $generated += $suggestions->count();
        }

        return response()->json(['message' => "Serendipity wave triggered", 'suggestions_generated' => $generated]);
    }
}
```

- [ ] **Step 4: Add routes**

Add to `routes/web.php`:

```php
use App\Http\Controllers\OrganizerActionController;

Route::post('/event/{event:slug}/actions/announce', [OrganizerActionController::class, 'announce'])->name('event.actions.announce');
Route::post('/event/{event:slug}/actions/serendipity-wave', [OrganizerActionController::class, 'serendipityWave'])->name('event.actions.serendipity-wave');
```

- [ ] **Step 5: Run tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=OrganizerActionControllerTest`
Expected: All 2 tests PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/OrganizerActionController.php routes/web.php tests/Feature/Http/OrganizerActionControllerTest.php
git commit -m "feat: add organizer actions (announcements, serendipity wave)"
```

---

## Task 5: Run Full Suite & Lint

- [ ] **Step 1: Run all tests**

Run: `php artisan test --compact`
Expected: All tests PASS

- [ ] **Step 2: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 3: Commit any fixes**

```bash
git add -A
git commit -m "style: apply Pint formatting"
```
