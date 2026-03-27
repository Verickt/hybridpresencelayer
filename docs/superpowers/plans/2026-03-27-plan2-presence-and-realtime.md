# Plan 2: Presence & Real-Time — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the real-time presence system: live status management, context badges, activity pulse, presence feed with filtering, and WebSocket broadcasting so all participants see updates in 1-2 seconds.

**Architecture:** Install Laravel Reverb for WebSockets. Use a single `PresenceStateChanged` broadcast event for all status/context changes (with `occurred_at` timestamp to prevent out-of-order updates). Use Reverb presence channels for live membership (join/leave) — no custom join/leave events needed. Domain logic (status transitions) is separated from presentation (badge text/colors). PresenceFeedController serves initial state from DB; client patches via WebSocket deltas and resyncs on reconnect.

**Tech Stack:** Laravel 13, Laravel Reverb, Laravel Echo, Inertia v3, Vue 3, Pest v4

**Depends on:** Plan 1 (data model) must be completed first.

## TDD Standard

- Start each task with failing tests before implementation.
- Add negative-path coverage for invalid statuses, missing event membership, invisible users, unauthorized access, reconnect/resync edge cases, and out-of-order real-time updates.
- For Inertia feed endpoints, add `assertInertia` coverage for filters, prop shape, and hidden participants.
- For the feed UI, add browser smoke coverage plus at least one browser flow for filtering/status changes and one failure or edge path.

---

## File Structure

### Broadcasting Infrastructure
```
config/broadcasting.php (modify — already exists in framework)
routes/channels.php (create)
```

### Events (app/Events/)
```
PresenceStateChanged.php — single event for all status/context changes (replaces separate join/leave/status events)
```
Note: Join/leave is handled by Reverb presence channels natively — no custom events needed.

### Services (app/Services/)
```
PresenceService.php — manages status transitions and activity tracking (domain logic only)
```

### Enums (app/Enums/)
```
ParticipantStatus.php — enum for valid statuses with display labels and colors
```

### Controllers (app/Http/Controllers/)
```
PresenceFeedController.php — serves the feed page with participant data
StatusController.php — handles manual status changes
```

### Vue Pages & Components (resources/js/)
```
pages/Event/Feed.vue — main presence feed page
components/presence/ParticipantCard.vue — individual participant card
components/presence/StatusIndicator.vue — colored status dot
components/presence/ContextBadge.vue — dynamic context label
components/presence/ActivityPulse.vue — glow animation on avatar
components/presence/PresenceFilters.vue — filter bar (status, type, tags)
components/presence/ParticipantAvatar.vue — initials avatar with interest-based color
```

---

## Task 1: Install Laravel Reverb

**Files:**
- Modify: `composer.json`
- Modify: `.env.example`
- Create: `routes/channels.php`

- [ ] **Step 1: Install Reverb**

Run: `php artisan install:broadcasting --no-interaction`

This installs `laravel/reverb`, creates `routes/channels.php`, publishes config, and installs the `laravel-echo` and `pusher-js` npm packages.

- [ ] **Step 2: Verify installation**

Run: `composer show laravel/reverb`
Expected: Shows reverb package info

Run: `cat routes/channels.php`
Expected: File exists with default channel

- [ ] **Step 3: Update .env.example with Reverb defaults**

Add to `.env.example` if not already present:

```
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=my-app-id
REVERB_APP_KEY=my-app-key
REVERB_APP_SECRET=my-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

- [ ] **Step 4: Install npm dependencies**

Run: `npm install`

- [ ] **Step 5: Commit**

```bash
git add composer.json composer.lock package.json package-lock.json routes/channels.php config/reverb.php .env.example resources/js/echo.js
git commit -m "feat: install Laravel Reverb for WebSocket broadcasting"
```

---

## Task 2: Presence Service

**Files:**
- Create: `app/Services/PresenceService.php`
- Create: `tests/Feature/Services/PresenceServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Services/PresenceServiceTest.php
<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\User;
use App\Services\PresenceService;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->user = User::factory()->create();
    $this->event->participants()->attach($this->user, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
    $this->service = app(PresenceService::class);
});

it('updates participant status', function () {
    $this->service->updateStatus($this->user, $this->event, 'busy');

    $pivot = $this->user->events()->where('event_id', $this->event->id)->first()->pivot;
    expect($pivot->status)->toBe('busy');
});

it('rejects invalid status values', function () {
    $this->service->updateStatus($this->user, $this->event, 'dancing');
})->throws(\InvalidArgumentException::class);

it('sets context badge when checking into a session', function () {
    $session = EventSession::factory()->create(['event_id' => $this->event->id, 'title' => 'Zero Trust Keynote']);

    $this->service->checkInToSession($this->user, $this->event, $session);

    $pivot = $this->user->events()->where('event_id', $this->event->id)->first()->pivot;
    expect($pivot->status)->toBe('in_session')
        ->and($pivot->context_badge)->toBe('Watching: Zero Trust Keynote');
});

it('clears context badge when checking out of a session', function () {
    $session = EventSession::factory()->create(['event_id' => $this->event->id]);
    $this->service->checkInToSession($this->user, $this->event, $session);

    $this->service->checkOutOfSession($this->user, $this->event);

    $pivot = $this->user->events()->where('event_id', $this->event->id)->first()->pivot;
    expect($pivot->status)->toBe('available')
        ->and($pivot->context_badge)->toBeNull();
});

it('sets context badge when visiting a booth', function () {
    $booth = \App\Models\Booth::factory()->create(['event_id' => $this->event->id, 'name' => 'CyberDefense AG Booth']);

    $this->service->checkInToBooth($this->user, $this->event, $booth);

    $pivot = $this->user->events()->where('event_id', $this->event->id)->first()->pivot;
    expect($pivot->status)->toBe('at_booth')
        ->and($pivot->context_badge)->toBe('At Booth: CyberDefense AG Booth');
});

it('marks participant as away after inactivity', function () {
    $this->service->markInactive($this->user, $this->event);

    $pivot = $this->user->events()->where('event_id', $this->event->id)->first()->pivot;
    expect($pivot->status)->toBe('away');
});

it('updates last_active_at on any action', function () {
    $this->service->touch($this->user, $this->event);

    $pivot = $this->user->events()->where('event_id', $this->event->id)->first()->pivot;
    expect($pivot->last_active_at)->not->toBeNull();
});

it('toggles invisible mode', function () {
    $this->service->toggleInvisible($this->user);

    expect($this->user->fresh()->is_invisible)->toBeTrue();

    $this->service->toggleInvisible($this->user);

    expect($this->user->fresh()->is_invisible)->toBeFalse();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=PresenceServiceTest`
Expected: FAIL — PresenceService doesn't exist

- [ ] **Step 3: Create PresenceService**

```php
// app/Services/PresenceService.php
<?php

namespace App\Services;

use App\Models\Booth;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionCheckIn;
use App\Models\User;

class PresenceService
{
    private const VALID_STATUSES = ['available', 'in_session', 'at_booth', 'busy', 'away'];

    public function updateStatus(User $user, Event $event, string $status): void
    {
        if (! in_array($status, self::VALID_STATUSES)) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }

        $user->events()->updateExistingPivot($event->id, [
            'status' => $status,
            'last_active_at' => now(),
        ]);
    }

    public function checkInToSession(User $user, Event $event, EventSession $session): void
    {
        // Check out of any current session and close booth visits first
        $this->checkOutOfSession($user, $event);
        \App\Models\BoothVisit::where('user_id', $user->id)->whereNull('left_at')->update(['left_at' => now()]);

        // Use updateOrCreate to allow re-entry after checkout
        SessionCheckIn::updateOrCreate(
            ['user_id' => $user->id, 'event_session_id' => $session->id],
            ['checked_out_at' => null]
        );

        $user->events()->updateExistingPivot($event->id, [
            'status' => 'in_session',
            'context_badge' => "Watching: {$session->title}",
            'last_active_at' => now(),
        ]);
    }

    public function checkOutOfSession(User $user, Event $event): void
    {
        // Mark any active check-ins as checked out
        SessionCheckIn::where('user_id', $user->id)
            ->whereNull('checked_out_at')
            ->update(['checked_out_at' => now()]);

        $user->events()->updateExistingPivot($event->id, [
            'status' => 'available',
            'context_badge' => null,
            'last_active_at' => now(),
        ]);
    }

    public function checkInToBooth(User $user, Event $event, Booth $booth): void
    {
        $user->events()->updateExistingPivot($event->id, [
            'status' => 'at_booth',
            'context_badge' => "At Booth: {$booth->name}",
            'last_active_at' => now(),
        ]);
    }

    public function markInactive(User $user, Event $event): void
    {
        $user->events()->updateExistingPivot($event->id, [
            'status' => 'away',
        ]);
    }

    public function touch(User $user, Event $event): void
    {
        $user->events()->updateExistingPivot($event->id, [
            'last_active_at' => now(),
        ]);
    }

    public function toggleInvisible(User $user): void
    {
        $user->update(['is_invisible' => ! $user->is_invisible]);
    }
}
```

- [ ] **Step 4: Run tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=PresenceServiceTest`
Expected: All 8 tests PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/PresenceService.php tests/Feature/Services/PresenceServiceTest.php
git commit -m "feat: add PresenceService for status management and context badges"
```

---

## Task 3: Broadcast Event & Status Enum

**Files:**
- Create: `app/Enums/ParticipantStatus.php`
- Create: `app/Events/PresenceStateChanged.php`
- Create: `tests/Feature/Events/PresenceBroadcastTest.php`

Note: Join/leave is handled natively by Reverb presence channels — no custom events needed. Only status/context changes need explicit broadcasting.

- [ ] **Step 1: Create ParticipantStatus enum**

```php
// app/Enums/ParticipantStatus.php
<?php

namespace App\Enums;

enum ParticipantStatus: string
{
    case Available = 'available';
    case InSession = 'in_session';
    case AtBooth = 'at_booth';
    case Busy = 'busy';
    case Away = 'away';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::InSession => 'In Session',
            self::AtBooth => 'At Booth',
            self::Busy => 'Busy',
            self::Away => 'Away',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Available => 'green',
            self::InSession => 'purple',
            self::AtBooth => 'blue',
            self::Busy => 'red',
            self::Away => 'gray',
        };
    }
}
```

- [ ] **Step 2: Write the failing test**

```php
// tests/Feature/Events/PresenceBroadcastTest.php
<?php

use App\Events\PresenceStateChanged;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Event as EventFacade;

it('broadcasts state change on the event presence channel', function () {
    EventFacade::fake([PresenceStateChanged::class]);

    $event = Event::factory()->live()->create();
    $user = User::factory()->create();
    $event->participants()->attach($user, ['participant_type' => 'physical', 'status' => 'available']);

    PresenceStateChanged::dispatch($event, $user, 'busy');

    EventFacade::assertDispatched(PresenceStateChanged::class, function ($e) use ($event, $user) {
        return $e->event->id === $event->id
            && $e->user->id === $user->id
            && $e->status === 'busy';
    });
});

it('broadcasts on the correct channel', function () {
    $event = Event::factory()->live()->create();
    $user = User::factory()->create();

    $broadcastEvent = new PresenceStateChanged($event, $user, 'available');

    expect($broadcastEvent->broadcastOn()->name)->toBe("private-event.{$event->id}.presence");
});

it('includes occurred_at timestamp to prevent out-of-order updates', function () {
    $event = Event::factory()->live()->create();
    $user = User::factory()->create();
    $event->participants()->attach($user, ['participant_type' => 'physical', 'status' => 'available']);

    $broadcastEvent = new PresenceStateChanged($event, $user, 'busy');
    $data = $broadcastEvent->broadcastWith();

    expect($data)->toHaveKey('occurred_at')
        ->and($data['occurred_at'])->toBeString();
});

it('does not broadcast when status has not changed', function () {
    EventFacade::fake([PresenceStateChanged::class]);

    $event = Event::factory()->live()->create();
    $user = User::factory()->create();
    $event->participants()->attach($user, ['participant_type' => 'physical', 'status' => 'available']);

    // Dispatch with same status — should be a no-op
    PresenceStateChanged::dispatchIf(false, $event, $user, 'available');

    EventFacade::assertNotDispatched(PresenceStateChanged::class);
});
```

- [ ] **Step 3: Run test to verify it fails**

Run: `php artisan test --compact --filter=PresenceBroadcastTest`
Expected: FAIL

- [ ] **Step 4: Create PresenceStateChanged event**

Run: `php artisan make:event PresenceStateChanged --no-interaction`

```php
// app/Events/PresenceStateChanged.php
<?php

namespace App\Events;

use App\Models\Event;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PresenceStateChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Event $event,
        public User $user,
        public string $status,
        public ?string $contextBadge = null,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("event.{$this->event->id}.presence");
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'name' => $this->user->name,
            'status' => $this->status,
            'context_badge' => $this->contextBadge,
            'participant_type' => $this->user->events()
                ->where('event_id', $this->event->id)
                ->first()?->pivot?->participant_type,
            'occurred_at' => now()->toISOString(),
        ];
    }
}
```

Note: Uses `ShouldBroadcastNow` instead of `ShouldBroadcast` to avoid stale state from queued broadcasts.

- [ ] **Step 5: Update PresenceService to dispatch events (only when status actually changes)**

Add to `app/Services/PresenceService.php` — dispatch only when the status actually changed:

```php
use App\Events\PresenceStateChanged;

// In updateStatus(), after the pivot update:
$previousStatus = $pivot->status ?? null;
// ... do the update ...
if ($status !== $previousStatus) {
    PresenceStateChanged::dispatch($event, $user, $status);
}

// In checkInToSession(), after the update:
PresenceStateChanged::dispatch($event, $user, 'in_session', "Watching: {$session->title}");

// In checkOutOfSession(), after the update:
PresenceStateChanged::dispatch($event, $user, 'available');

// In checkInToBooth(), after the update:
PresenceStateChanged::dispatch($event, $user, 'at_booth', "At Booth: {$booth->name}");

// In markInactive(), after the update:
PresenceStateChanged::dispatch($event, $user, 'away');
```

- [ ] **Step 7: Add channel authorization**

Update `routes/channels.php`:

```php
<?php

use App\Models\Event;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('event.{eventId}.presence', function ($user, int $eventId) {
    return $user->events()->where('event_id', $eventId)->exists();
});
```

- [ ] **Step 8: Run tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=PresenceBroadcastTest`
Expected: All 4 tests PASS

- [ ] **Step 9: Commit**

```bash
git add app/Events/ParticipantStatusChanged.php app/Events/ParticipantJoinedEvent.php app/Events/ParticipantLeftEvent.php app/Services/PresenceService.php routes/channels.php tests/Feature/Events/PresenceBroadcastTest.php
git commit -m "feat: add presence broadcast events and channel authorization"
```

---

## Task 4: Presence Feed Controller

**Files:**
- Create: `app/Http/Controllers/PresenceFeedController.php`
- Create: `app/Http/Controllers/StatusController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Http/PresenceFeedTest.php`
- Create: `tests/Feature/Http/StatusTest.php`

- [ ] **Step 1: Write the failing tests**

```php
// tests/Feature/Http/PresenceFeedTest.php
<?php

use App\Models\Event;
use App\Models\InterestTag;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->user = User::factory()->create();
    $this->event->participants()->attach($this->user, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
});

it('returns the presence feed page', function () {
    $response = $this->actingAs($this->user)
        ->get(route('event.feed', $this->event));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Event/Feed')
            ->has('participants')
            ->has('event')
        );
});

it('excludes invisible participants', function () {
    $invisible = User::factory()->create(['is_invisible' => true]);
    $this->event->participants()->attach($invisible, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('event.feed', $this->event));

    $response->assertInertia(fn ($page) => $page
        ->has('participants', 1)
    );
});

it('filters by participant type', function () {
    $remote = User::factory()->create();
    $this->event->participants()->attach($remote, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('event.feed', ['event' => $this->event, 'type' => 'physical']));

    $response->assertInertia(fn ($page) => $page
        ->has('participants', 1)
    );
});

it('filters by status', function () {
    $busy = User::factory()->create();
    $this->event->participants()->attach($busy, [
        'participant_type' => 'physical',
        'status' => 'busy',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('event.feed', ['event' => $this->event, 'status' => 'available']));

    $response->assertInertia(fn ($page) => $page
        ->has('participants', 1)
    );
});

it('requires authentication', function () {
    $response = $this->get(route('event.feed', $this->event));

    $response->assertRedirect(route('login'));
});
```

```php
// tests/Feature/Http/StatusTest.php
<?php

use App\Models\Event;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->user = User::factory()->create();
    $this->event->participants()->attach($this->user, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
});

it('updates status via API', function () {
    $response = $this->actingAs($this->user)
        ->patch(route('event.status.update', $this->event), [
            'status' => 'busy',
        ]);

    $response->assertOk();

    $pivot = $this->user->events()->where('event_id', $this->event->id)->first()->pivot;
    expect($pivot->status)->toBe('busy');
});

it('rejects invalid status', function () {
    $response = $this->actingAs($this->user)
        ->patch(route('event.status.update', $this->event), [
            'status' => 'invalid',
        ]);

    $response->assertUnprocessable();
});

it('toggles invisible mode', function () {
    $response = $this->actingAs($this->user)
        ->patch(route('event.status.invisible', $this->event));

    $response->assertOk();
    expect($this->user->fresh()->is_invisible)->toBeTrue();
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter="PresenceFeedTest|StatusTest"`
Expected: FAIL

- [ ] **Step 3: Create PresenceFeedController**

Run: `php artisan make:controller PresenceFeedController --no-interaction`

```php
// app/Http/Controllers/PresenceFeedController.php
<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PresenceFeedController extends Controller
{
    public function __invoke(Request $request, Event $event): Response
    {
        $query = $event->participants()
            ->where('users.is_invisible', false)
            ->where('users.id', '!=', $request->user()->id);

        if ($request->filled('type')) {
            $query->wherePivot('participant_type', $request->input('type'));
        }

        if ($request->filled('status')) {
            $query->wherePivot('status', $request->input('status'));
        }

        if ($request->filled('tag')) {
            $tagId = $request->input('tag');
            $query->whereHas('interestTags', fn ($q) => $q->where('interest_tags.id', $tagId)
                ->where('user_interest_tag.event_id', $event->id)
            );
        }

        $participants = $query->with(['interestTags' => fn ($q) => $q->wherePivot('event_id', $event->id)])
            ->get()
            ->map(fn ($participant) => [
                'id' => $participant->id,
                'name' => $participant->name,
                'company' => $participant->company,
                'role_title' => $participant->role_title,
                'intent' => $participant->intent,
                'participant_type' => $participant->pivot->participant_type,
                'status' => $participant->pivot->status,
                'context_badge' => $participant->pivot->context_badge,
                'icebreaker_answer' => $participant->pivot->icebreaker_answer,
                'open_to_call' => $participant->pivot->open_to_call,
                'last_active_at' => $participant->pivot->last_active_at,
                'interest_tags' => $participant->interestTags->pluck('name'),
            ]);

        return Inertia::render('Event/Feed', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'slug' => $event->slug,
            ],
            'participants' => $participants,
            'filters' => $request->only(['type', 'status', 'tag']),
        ]);
    }
}
```

- [ ] **Step 4: Create StatusController**

Run: `php artisan make:controller StatusController --no-interaction`

```php
// app/Http/Controllers/StatusController.php
<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\PresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    public function update(Request $request, Event $event, PresenceService $presenceService): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:available,in_session,at_booth,busy,away'],
        ]);

        $presenceService->updateStatus($request->user(), $event, $validated['status']);

        return response()->json(['message' => 'Status updated']);
    }

    public function toggleInvisible(Request $request, Event $event, PresenceService $presenceService): JsonResponse
    {
        $presenceService->toggleInvisible($request->user());

        return response()->json(['message' => 'Visibility toggled']);
    }
}
```

- [ ] **Step 5: Add routes**

Add to `routes/web.php`:

```php
use App\Http\Controllers\PresenceFeedController;
use App\Http\Controllers\StatusController;

Route::middleware(['auth'])->group(function () {
    Route::get('/event/{event:slug}/feed', PresenceFeedController::class)->name('event.feed');
    Route::patch('/event/{event:slug}/status', [StatusController::class, 'update'])->name('event.status.update');
    Route::patch('/event/{event:slug}/invisible', [StatusController::class, 'toggleInvisible'])->name('event.status.invisible');
});
```

- [ ] **Step 6: Run tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter="PresenceFeedTest|StatusTest"`
Expected: All 8 tests PASS

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/PresenceFeedController.php app/Http/Controllers/StatusController.php routes/web.php tests/Feature/Http/PresenceFeedTest.php tests/Feature/Http/StatusTest.php
git commit -m "feat: add presence feed controller with filtering and status management"
```

---

## Task 5: Vue Presence Components

**Files:**
- Create: `resources/js/pages/Event/Feed.vue`
- Create: `resources/js/components/presence/ParticipantCard.vue`
- Create: `resources/js/components/presence/StatusIndicator.vue`
- Create: `resources/js/components/presence/ContextBadge.vue`
- Create: `resources/js/components/presence/ActivityPulse.vue`
- Create: `resources/js/components/presence/PresenceFilters.vue`
- Create: `resources/js/components/presence/ParticipantAvatar.vue`

- [ ] **Step 1: Create ParticipantAvatar component**

```vue
<!-- resources/js/components/presence/ParticipantAvatar.vue -->
<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
    name: string
    interestTags: string[]
    activityLevel?: number
}>()

const initials = computed(() => {
    return props.name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2)
})

const tagColors: Record<string, string> = {
    'Zero Trust': '#3B82F6',
    'Cloud Migration': '#8B5CF6',
    'DevOps': '#F59E0B',
    'AI/ML': '#EF4444',
    'Cybersecurity': '#10B981',
    'Data Privacy': '#6366F1',
    'IoT': '#EC4899',
    'Blockchain': '#14B8A6',
}

const bgColor = computed(() => {
    const primaryTag = props.interestTags[0] ?? ''
    return tagColors[primaryTag] ?? '#6B7280'
})
</script>

<template>
    <div
        class="relative inline-flex items-center justify-center rounded-full text-white font-semibold"
        :style="{ backgroundColor: bgColor, width: '48px', height: '48px' }"
    >
        <span class="text-sm">{{ initials }}</span>
        <ActivityPulse v-if="activityLevel" :level="activityLevel" />
    </div>
</template>
```

- [ ] **Step 2: Create StatusIndicator component**

```vue
<!-- resources/js/components/presence/StatusIndicator.vue -->
<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
    status: string
}>()

const statusConfig: Record<string, { color: string; label: string }> = {
    available: { color: 'bg-green-500', label: 'Available' },
    in_session: { color: 'bg-purple-500', label: 'In Session' },
    at_booth: { color: 'bg-blue-500', label: 'At Booth' },
    busy: { color: 'bg-red-500', label: 'Busy' },
    away: { color: 'bg-gray-400', label: 'Away' },
}

const config = computed(() => statusConfig[props.status] ?? statusConfig.away)
</script>

<template>
    <span class="inline-flex items-center gap-1.5">
        <span class="h-2.5 w-2.5 rounded-full" :class="config.color" />
        <span class="text-xs text-muted-foreground">{{ config.label }}</span>
    </span>
</template>
```

- [ ] **Step 3: Create ContextBadge component**

```vue
<!-- resources/js/components/presence/ContextBadge.vue -->
<script setup lang="ts">
defineProps<{
    badge: string | null
}>()
</script>

<template>
    <span v-if="badge" class="inline-flex items-center rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium text-muted-foreground">
        {{ badge }}
    </span>
</template>
```

- [ ] **Step 4: Create ActivityPulse component**

```vue
<!-- resources/js/components/presence/ActivityPulse.vue -->
<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
    level: number
}>()

const opacity = computed(() => Math.min(props.level / 10, 1))
</script>

<template>
    <span
        v-if="level > 0"
        class="absolute inset-0 rounded-full animate-ping"
        :style="{ backgroundColor: `rgba(59, 130, 246, ${opacity * 0.4})`, animationDuration: `${2 - opacity}s` }"
    />
</template>
```

- [ ] **Step 5: Create PresenceFilters component**

```vue
<!-- resources/js/components/presence/PresenceFilters.vue -->
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
```

- [ ] **Step 6: Create ParticipantCard component**

```vue
<!-- resources/js/components/presence/ParticipantCard.vue -->
<script setup lang="ts">
import ParticipantAvatar from './ParticipantAvatar.vue'
import StatusIndicator from './StatusIndicator.vue'
import ContextBadge from './ContextBadge.vue'
import { MapPin, Globe } from 'lucide-vue-next'

defineProps<{
    participant: {
        id: number
        name: string
        company?: string
        role_title?: string
        intent?: string
        participant_type: string
        status: string
        context_badge: string | null
        icebreaker_answer?: string
        open_to_call: boolean
        interest_tags: string[]
    }
}>()

defineEmits<{
    ping: [userId: number]
}>()
</script>

<template>
    <div class="flex items-start gap-4 rounded-xl border p-4 transition hover:bg-muted/50">
        <ParticipantAvatar
            :name="participant.name"
            :interest-tags="participant.interest_tags"
        />

        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
                <h3 class="font-semibold truncate">{{ participant.name }}</h3>
                <component
                    :is="participant.participant_type === 'physical' ? MapPin : Globe"
                    class="h-4 w-4 shrink-0 text-muted-foreground"
                />
                <StatusIndicator :status="participant.status" />
            </div>

            <p v-if="participant.company" class="text-sm text-muted-foreground">
                {{ participant.role_title ? `${participant.role_title} at ` : '' }}{{ participant.company }}
            </p>

            <ContextBadge :badge="participant.context_badge" />

            <div class="mt-2 flex flex-wrap gap-1.5">
                <span
                    v-for="tag in participant.interest_tags"
                    :key="tag"
                    class="inline-flex items-center rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
                >
                    {{ tag }}
                </span>
            </div>

            <p v-if="participant.icebreaker_answer" class="mt-2 text-sm italic text-muted-foreground">
                "{{ participant.icebreaker_answer }}"
            </p>
        </div>

        <button
            @click="$emit('ping', participant.id)"
            class="shrink-0 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
        >
            👋 Ping
        </button>
    </div>
</template>
```

- [ ] **Step 7: Create Feed page**

```vue
<!-- resources/js/pages/Event/Feed.vue -->
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import ParticipantCard from '@/components/presence/ParticipantCard.vue'
import PresenceFilters from '@/components/presence/PresenceFilters.vue'

const props = defineProps<{
    event: { id: number; name: string; slug: string }
    participants: Array<{
        id: number
        name: string
        company?: string
        role_title?: string
        intent?: string
        participant_type: string
        status: string
        context_badge: string | null
        icebreaker_answer?: string
        open_to_call: boolean
        interest_tags: string[]
    }>
    filters: { type?: string; status?: string; tag?: string }
}>()

function handlePing(userId: number) {
    // Will be implemented in Plan 4 (Interactions)
    console.log('Ping user:', userId)
}
</script>

<template>
    <AppLayout :title="event.name">
        <div class="mx-auto max-w-2xl px-4 py-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold">{{ event.name }}</h1>
                <p class="text-muted-foreground">
                    {{ participants.length }} participant{{ participants.length !== 1 ? 's' : '' }} active
                </p>
            </div>

            <PresenceFilters :filters="filters" :event-slug="event.slug" />

            <div class="mt-6 space-y-3">
                <ParticipantCard
                    v-for="participant in participants"
                    :key="participant.id"
                    :participant="participant"
                    @ping="handlePing"
                />

                <p v-if="participants.length === 0" class="py-12 text-center text-muted-foreground">
                    No participants match your filters.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
```

- [ ] **Step 8: Commit**

```bash
git add resources/js/pages/Event/Feed.vue resources/js/components/presence/
git commit -m "feat: add presence feed page with participant cards, filters, and status components"
```

---

## Task 6: Real-Time Listener on Feed

**Files:**
- Modify: `resources/js/pages/Event/Feed.vue`

- [ ] **Step 1: Add Echo listener to Feed page**

Update the `<script setup>` section of `resources/js/pages/Event/Feed.vue`:

```vue
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import ParticipantCard from '@/components/presence/ParticipantCard.vue'
import PresenceFilters from '@/components/presence/PresenceFilters.vue'
import { ref, onMounted, onUnmounted } from 'vue'

const props = defineProps<{
    event: { id: number; name: string; slug: string }
    participants: Array<{
        id: number
        name: string
        company?: string
        role_title?: string
        intent?: string
        participant_type: string
        status: string
        context_badge: string | null
        icebreaker_answer?: string
        open_to_call: boolean
        interest_tags: string[]
    }>
    filters: { type?: string; status?: string; tag?: string }
}>()

const liveParticipants = ref([...props.participants])

function handlePing(userId: number) {
    console.log('Ping user:', userId)
}

// Track last received timestamp to discard out-of-order updates
const lastOccurredAt = ref<Record<number, string>>({})

onMounted(() => {
    const channel = window.Echo.join(`event.${props.event.id}.presence`)

    // Reverb presence channel handles join/leave natively
    channel
        .here((users: Array<{ id: number; name: string }>) => {
            // Initial presence list — could cross-reference with server data
        })
        .joining((user: { id: number; name: string }) => {
            if (!liveParticipants.value.find(p => p.id === user.id)) {
                // Fetch full participant data via Inertia reload or API
                router.reload({ only: ['participants'] })
            }
        })
        .leaving((user: { id: number }) => {
            // Don't remove — they may still be on another tab. Let status go to 'away' via server.
        })

    // Listen for state changes (status, context badge)
    channel.listen('PresenceStateChanged', (data: {
        user_id: number
        status: string
        context_badge: string | null
        participant_type: string
        occurred_at: string
    }) => {
        // Discard out-of-order updates
        const last = lastOccurredAt.value[data.user_id]
        if (last && data.occurred_at < last) return
        lastOccurredAt.value[data.user_id] = data.occurred_at

        const participant = liveParticipants.value.find(p => p.id === data.user_id)
        if (participant) {
            participant.status = data.status
            participant.context_badge = data.context_badge
        }
    })
})

onUnmounted(() => {
    window.Echo.leave(`event.${props.event.id}.presence`)
})
</script>
```

Update the template to use `liveParticipants` instead of `participants`:

```vue
<!-- In the template, change participants to liveParticipants -->
<ParticipantCard
    v-for="participant in liveParticipants"
    :key="participant.id"
    :participant="participant"
    @ping="handlePing"
/>

<p v-if="liveParticipants.length === 0" class="py-12 text-center text-muted-foreground">
    No participants match your filters.
</p>
```

And update the participant count:

```vue
<p class="text-muted-foreground">
    {{ liveParticipants.length }} participant{{ liveParticipants.length !== 1 ? 's' : '' }} active
</p>
```

- [ ] **Step 2: Add Echo type declaration**

Create or update `resources/js/echo.d.ts`:

```typescript
import Echo from 'laravel-echo'

declare global {
    interface Window {
        Echo: Echo
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/Event/Feed.vue resources/js/echo.d.ts
git commit -m "feat: add real-time presence updates via Echo on feed page"
```

---

## Task 7: Run Full Suite & Lint

- [ ] **Step 1: Run all tests**

Run: `php artisan test --compact`
Expected: All tests PASS

- [ ] **Step 2: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 3: Build frontend to verify no compilation errors**

Run: `npm run build`
Expected: Build succeeds

- [ ] **Step 4: Commit any fixes**

```bash
git add -A
git commit -m "style: apply Pint formatting and fix build"
```
