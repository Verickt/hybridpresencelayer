# Plan 6: Booths — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build booth check-in, visitor feed, booth staff tools (announcements, proactive pings), booth discovery, and lead capture dashboard.

**Architecture:** `BoothController` handles listing and detail. `BoothVisitController` manages check-in/out with anonymous browsing support (closes active sessions on booth check-in). `BoothStaffController` provides the staff-facing interface. Lead data is derived from BoothVisit records. All routes use scoped bindings (`->scopeBindings()`) to prevent cross-event access. Invisible users are filtered from visitor lists.

**Tech Stack:** Laravel 13, Reverb, Inertia v3, Vue 3, Pest v4

**Depends on:** Plan 1 (models), Plan 2 (presence)

## TDD Standard

- Start each task with failing tests before implementation.
- Cover negative and edge cases: anonymous browsing rules, cross-event access, invisible visitors, double check-in, invalid staff actions, export authorization, and session-to-booth attribution errors.
- For booth Inertia endpoints, add `assertInertia` coverage for public vs staff props and hidden visitor data.
- Add browser smoke coverage plus real browser tests for one visitor flow and one staff/failure-path flow.

---

## Task 1: Booth Controller (Discovery & Detail)

**Files:**
- Create: `app/Http/Controllers/BoothController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Http/BoothControllerTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Http/BoothControllerTest.php
<?php

use App\Models\Booth;
use App\Models\Event;
use App\Models\InterestTag;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->user = User::factory()->create();
    $tag = InterestTag::factory()->create();
    $this->user->interestTags()->attach($tag, ['event_id' => $this->event->id]);
    $this->event->participants()->attach($this->user, ['participant_type' => 'physical', 'status' => 'available']);
    $this->booth = Booth::factory()->create(['event_id' => $this->event->id]);
    $this->booth->interestTags()->attach($tag);
});

it('lists booths sorted by relevance', function () {
    $response = $this->actingAs($this->user)
        ->get(route('event.booths', $this->event));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Event/Booths')
            ->has('booths')
        );
});

it('shows booth detail with visitors and staff', function () {
    $response = $this->actingAs($this->user)
        ->get(route('event.booths.show', [$this->event, $this->booth]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Event/BoothDetail')
            ->has('booth')
            ->has('visitors')
            ->has('staff')
        );
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=BoothControllerTest`
Expected: FAIL

- [ ] **Step 3: Create BoothController**

Run: `php artisan make:controller BoothController --no-interaction`

```php
// app/Http/Controllers/BoothController.php
<?php

namespace App\Http\Controllers;

use App\Models\Booth;
use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BoothController extends Controller
{
    public function index(Request $request, Event $event): Response
    {
        $userTagIds = $request->user()->interestTags()
            ->wherePivot('event_id', $event->id)
            ->pluck('interest_tags.id');

        $booths = $event->booths()
            ->with(['interestTags', 'staff:id,name'])
            ->withCount(['visits' => fn ($q) => $q->where('is_anonymous', false)])
            ->get()
            ->map(function (Booth $booth) use ($userTagIds) {
                $boothTagIds = $booth->interestTags->pluck('id');
                $relevance = $boothTagIds->intersect($userTagIds)->count();

                return [
                    'id' => $booth->id,
                    'name' => $booth->name,
                    'company' => $booth->company,
                    'description' => $booth->description,
                    'interest_tags' => $booth->interestTags->pluck('name'),
                    'visitor_count' => $booth->visits_count,
                    'staff' => $booth->staff->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
                    'relevance' => $relevance,
                ];
            })
            ->sortByDesc('relevance')
            ->values();

        return Inertia::render('Event/Booths', [
            'event' => ['id' => $event->id, 'name' => $event->name, 'slug' => $event->slug],
            'booths' => $booths,
        ]);
    }

    public function show(Request $request, Event $event, Booth $booth): Response
    {
        $visitors = $booth->visits()
            ->where('is_anonymous', false)
            ->whereNull('left_at')
            ->with('user:id,name,company')
            ->get()
            ->map(fn ($v) => [
                'id' => $v->user->id,
                'name' => $v->user->name,
                'company' => $v->user->company,
                'participant_type' => $v->participant_type,
                'entered_at' => $v->entered_at->toISOString(),
            ]);

        $staff = $booth->staff()->get()->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'status' => $s->events()->where('event_id', $event->id)->first()?->pivot?->status,
        ]);

        return Inertia::render('Event/BoothDetail', [
            'event' => ['id' => $event->id, 'name' => $event->name, 'slug' => $event->slug],
            'booth' => [
                'id' => $booth->id,
                'name' => $booth->name,
                'company' => $booth->company,
                'description' => $booth->description,
                'content_links' => $booth->content_links,
                'interest_tags' => $booth->interestTags->pluck('name'),
            ],
            'visitors' => $visitors,
            'staff' => $staff,
        ]);
    }
}
```

- [ ] **Step 4: Add routes**

Add to `routes/web.php` inside the auth middleware group:

```php
use App\Http\Controllers\BoothController;

Route::get('/event/{event:slug}/booths', [BoothController::class, 'index'])->name('event.booths');
Route::get('/event/{event:slug}/booths/{booth}', [BoothController::class, 'show'])->name('event.booths.show')->scopeBindings();
```

- [ ] **Step 5: Run tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=BoothControllerTest`
Expected: All 2 tests PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/BoothController.php routes/web.php tests/Feature/Http/BoothControllerTest.php
git commit -m "feat: add BoothController with discovery and detail views"
```

---

## Task 2: Booth Visit Controller

**Files:**
- Create: `app/Http/Controllers/BoothVisitController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Http/BoothVisitControllerTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Http/BoothVisitControllerTest.php
<?php

use App\Models\Booth;
use App\Models\BoothVisit;
use App\Models\Event;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->user = User::factory()->create();
    $this->event->participants()->attach($this->user, ['participant_type' => 'physical', 'status' => 'available']);
    $this->booth = Booth::factory()->create(['event_id' => $this->event->id]);
});

it('checks into a booth', function () {
    $response = $this->actingAs($this->user)
        ->post(route('event.booths.checkin', [$this->event, $this->booth]));

    $response->assertOk();
    expect(BoothVisit::count())->toBe(1);

    $pivot = $this->user->events()->where('event_id', $this->event->id)->first()->pivot;
    expect($pivot->status)->toBe('at_booth');
});

it('checks into a booth anonymously', function () {
    $response = $this->actingAs($this->user)
        ->post(route('event.booths.checkin', [$this->event, $this->booth]), [
            'anonymous' => true,
        ]);

    $response->assertOk();
    expect(BoothVisit::first()->is_anonymous)->toBeTrue();
});

it('checks out of a booth', function () {
    BoothVisit::create([
        'user_id' => $this->user->id,
        'booth_id' => $this->booth->id,
        'entered_at' => now(),
        'participant_type' => 'physical',
    ]);

    $response = $this->actingAs($this->user)
        ->delete(route('event.booths.checkout', [$this->event, $this->booth]));

    $response->assertOk();
    expect(BoothVisit::first()->left_at)->not->toBeNull();

    $pivot = $this->user->events()->where('event_id', $this->event->id)->first()->pivot;
    expect($pivot->status)->toBe('available');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=BoothVisitControllerTest`
Expected: FAIL

- [ ] **Step 3: Create BoothVisitController**

Run: `php artisan make:controller BoothVisitController --no-interaction`

```php
// app/Http/Controllers/BoothVisitController.php
<?php

namespace App\Http\Controllers;

use App\Models\Booth;
use App\Models\BoothVisit;
use App\Models\Event;
use App\Services\PresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoothVisitController extends Controller
{
    public function store(Request $request, Event $event, Booth $booth, PresenceService $presenceService): JsonResponse
    {
        $user = $request->user();
        $isAnonymous = $request->boolean('anonymous', false);

        $pivot = $user->events()->where('event_id', $event->id)->first()?->pivot;

        // Get last session if coming from one
        $lastSessionId = null;
        if ($pivot?->status === 'in_session') {
            $lastCheckIn = \App\Models\SessionCheckIn::where('user_id', $user->id)
                ->whereNull('checked_out_at')
                ->first();
            $lastSessionId = $lastCheckIn?->event_session_id;
        }

        BoothVisit::create([
            'user_id' => $user->id,
            'booth_id' => $booth->id,
            'is_anonymous' => $isAnonymous,
            'participant_type' => $pivot?->participant_type,
            'from_session_id' => $lastSessionId,
            'entered_at' => now(),
        ]);

        if (! $isAnonymous) {
            $presenceService->checkInToBooth($user, $event, $booth);
        }

        return response()->json(['message' => 'Checked in']);
    }

    public function destroy(Request $request, Event $event, Booth $booth, PresenceService $presenceService): JsonResponse
    {
        BoothVisit::where('user_id', $request->user()->id)
            ->where('booth_id', $booth->id)
            ->whereNull('left_at')
            ->update(['left_at' => now()]);

        $presenceService->updateStatus($request->user(), $event, 'available');

        $request->user()->events()->updateExistingPivot($event->id, [
            'context_badge' => null,
        ]);

        return response()->json(['message' => 'Checked out']);
    }
}
```

- [ ] **Step 4: Add routes**

Add to `routes/web.php`:

```php
use App\Http\Controllers\BoothVisitController;

Route::post('/event/{event:slug}/booths/{booth}/checkin', [BoothVisitController::class, 'store'])->name('event.booths.checkin')->scopeBindings();
Route::delete('/event/{event:slug}/booths/{booth}/checkout', [BoothVisitController::class, 'destroy'])->name('event.booths.checkout')->scopeBindings();
```

- [ ] **Step 5: Run tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=BoothVisitControllerTest`
Expected: All 3 tests PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/BoothVisitController.php routes/web.php tests/Feature/Http/BoothVisitControllerTest.php
git commit -m "feat: add booth check-in/out with anonymous browsing and session attribution"
```

---

## Task 3: Booth Staff Controller & Lead Dashboard

**Files:**
- Create: `app/Http/Controllers/BoothStaffController.php`
- Create: `app/Events/BoothAnnouncement.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Http/BoothStaffControllerTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Http/BoothStaffControllerTest.php
<?php

use App\Models\Booth;
use App\Models\BoothVisit;
use App\Models\Event;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->staff = User::factory()->create();
    $this->event->participants()->attach($this->staff, ['participant_type' => 'physical', 'status' => 'available']);
    $this->booth = Booth::factory()->create(['event_id' => $this->event->id]);
    $this->booth->staff()->attach($this->staff);
});

it('shows lead dashboard for booth staff', function () {
    BoothVisit::factory(5)->create(['booth_id' => $this->booth->id, 'entered_at' => now()]);

    $response = $this->actingAs($this->staff)
        ->get(route('event.booths.leads', [$this->event, $this->booth]));

    $response->assertOk()
        ->assertJsonStructure(['data' => ['total_visitors', 'physical_count', 'remote_count', 'leads']]);
});

it('sends a booth announcement', function () {
    $response = $this->actingAs($this->staff)
        ->post(route('event.booths.announce', [$this->event, $this->booth]), [
            'message' => 'Live demo starting in 5 minutes!',
        ]);

    $response->assertOk();
});

it('rejects non-staff from lead dashboard', function () {
    $stranger = User::factory()->create();

    $response = $this->actingAs($stranger)
        ->get(route('event.booths.leads', [$this->event, $this->booth]));

    $response->assertForbidden();
});

it('exports leads as CSV', function () {
    BoothVisit::factory(3)->create(['booth_id' => $this->booth->id, 'entered_at' => now()]);

    $response = $this->actingAs($this->staff)
        ->get(route('event.booths.leads.export', [$this->event, $this->booth]));

    $response->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=BoothStaffControllerTest`
Expected: FAIL

- [ ] **Step 3: Create BoothAnnouncement event**

```php
// app/Events/BoothAnnouncement.php
<?php

namespace App\Events;

use App\Models\Booth;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BoothAnnouncement implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Booth $booth,
        public string $message,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("event.{$this->booth->event_id}.presence");
    }

    public function broadcastWith(): array
    {
        return [
            'booth_id' => $this->booth->id,
            'booth_name' => $this->booth->name,
            'message' => $this->message,
        ];
    }
}
```

- [ ] **Step 4: Create BoothStaffController**

Run: `php artisan make:controller BoothStaffController --no-interaction`

```php
// app/Http/Controllers/BoothStaffController.php
<?php

namespace App\Http\Controllers;

use App\Events\BoothAnnouncement;
use App\Models\Booth;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BoothStaffController extends Controller
{
    public function leads(Request $request, Event $event, Booth $booth): JsonResponse
    {
        $this->authorizeStaff($request, $booth);

        $visits = $booth->visits()
            ->where('is_anonymous', false)
            ->with('user:id,name,email,company,role_title')
            ->orderByDesc('entered_at')
            ->get();

        $physicalCount = $visits->where('participant_type', 'physical')->count();
        $remoteCount = $visits->where('participant_type', 'remote')->count();

        $leads = $visits->map(fn ($v) => [
            'id' => $v->id,
            'name' => $v->user->name,
            'email' => $v->user->email,
            'company' => $v->user->company,
            'role_title' => $v->user->role_title,
            'participant_type' => $v->participant_type,
            'entered_at' => $v->entered_at->toISOString(),
            'duration_minutes' => $v->durationInMinutes(),
            'from_session_id' => $v->from_session_id,
        ]);

        return response()->json([
            'data' => [
                'total_visitors' => $visits->count(),
                'physical_count' => $physicalCount,
                'remote_count' => $remoteCount,
                'leads' => $leads,
            ],
        ]);
    }

    public function announce(Request $request, Event $event, Booth $booth): JsonResponse
    {
        $this->authorizeStaff($request, $booth);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:280'],
        ]);

        BoothAnnouncement::dispatch($booth, $validated['message']);

        return response()->json(['message' => 'Announcement sent']);
    }

    public function exportLeads(Request $request, Event $event, Booth $booth): StreamedResponse
    {
        $this->authorizeStaff($request, $booth);

        $visits = $booth->visits()
            ->where('is_anonymous', false)
            ->with('user:id,name,email,company,role_title')
            ->orderByDesc('entered_at')
            ->get();

        return response()->streamDownload(function () use ($visits) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Email', 'Company', 'Role', 'Type', 'Duration (min)', 'Visited At']);

            foreach ($visits as $visit) {
                fputcsv($handle, [
                    $visit->user->name,
                    $visit->user->email,
                    $visit->user->company,
                    $visit->user->role_title,
                    $visit->participant_type,
                    $visit->durationInMinutes(),
                    $visit->entered_at->toISOString(),
                ]);
            }

            fclose($handle);
        }, "{$booth->company}-leads.csv", [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function authorizeStaff(Request $request, Booth $booth): void
    {
        abort_unless($booth->staff()->where('user_id', $request->user()->id)->exists(), 403);
    }
}
```

- [ ] **Step 5: Add routes**

Add to `routes/web.php`:

```php
use App\Http\Controllers\BoothStaffController;

Route::get('/event/{event:slug}/booths/{booth}/leads', [BoothStaffController::class, 'leads'])->name('event.booths.leads')->scopeBindings();
Route::post('/event/{event:slug}/booths/{booth}/announce', [BoothStaffController::class, 'announce'])->name('event.booths.announce')->scopeBindings();
Route::get('/event/{event:slug}/booths/{booth}/leads/export', [BoothStaffController::class, 'exportLeads'])->name('event.booths.leads.export')->scopeBindings();
```

- [ ] **Step 6: Run tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=BoothStaffControllerTest`
Expected: All 4 tests PASS

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/BoothStaffController.php app/Events/BoothAnnouncement.php routes/web.php tests/Feature/Http/BoothStaffControllerTest.php
git commit -m "feat: add booth staff controller with leads, announcements, and CSV export"
```

---

## Task 4: Run Full Suite & Lint

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
