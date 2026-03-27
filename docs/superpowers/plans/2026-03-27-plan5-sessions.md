# Plan 5: Sessions — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build session check-in flow, live participant list, Q&A with upvoting, reaction stream, and post-session matchmaking trigger.

**Architecture:** `SessionController` handles CRUD and check-in. `SessionReactionController` and `SessionQuestionController` handle engagement features. Post-session matchmaking triggers via the existing `SuggestionService` when a session ends. Reactions and questions are broadcast in real-time. All routes use scoped bindings (`->scopeBindings()`) to ensure sessions belong to the correct event. Session check-ins use `updateOrCreate` instead of `create` to allow re-entry after checkout.

**Tech Stack:** Laravel 13, Reverb, Inertia v3, Vue 3, Pest v4

**Depends on:** Plan 1 (models), Plan 2 (presence), Plan 3 (discovery)

## TDD Standard

- Start each task with failing tests before implementation.
- Cover negative and edge cases: cross-event access, double check-in, checkout without active presence, invalid reactions, duplicate votes, late-session actions, and organizer-only mutations.
- For schedule/session Inertia endpoints, add `assertInertia` coverage for component props, counts, and privacy constraints.
- Add browser smoke coverage plus real browser tests for one participant flow and one organizer or failure-path flow.

---

## File Structure

### Controllers
```
app/Http/Controllers/SessionController.php
app/Http/Controllers/SessionCheckInController.php
app/Http/Controllers/SessionReactionController.php
app/Http/Controllers/SessionQuestionController.php
```

### Events
```
app/Events/SessionReactionSent.php
app/Events/SessionQuestionPosted.php
app/Events/SessionQuestionVoted.php
```

### Vue Pages & Components
```
resources/js/pages/Event/Sessions.vue — session schedule
resources/js/pages/Event/SessionDetail.vue — live session view
resources/js/components/session/SessionCard.vue
resources/js/components/session/ReactionStream.vue
resources/js/components/session/QuestionList.vue
resources/js/components/session/ParticipantSidebar.vue
```

---

## Task 1: Session Controller (Schedule & CRUD)

**Files:**
- Create: `app/Http/Controllers/SessionController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Http/SessionControllerTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Http/SessionControllerTest.php
<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->user = User::factory()->create();
    $this->event->participants()->attach($this->user, ['participant_type' => 'physical', 'status' => 'available']);
});

it('shows session schedule', function () {
    EventSession::factory(3)->create(['event_id' => $this->event->id]);

    $response = $this->actingAs($this->user)
        ->get(route('event.sessions', $this->event));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Event/Sessions')
            ->has('sessions', 3)
        );
});

it('shows session detail with participants', function () {
    $session = EventSession::factory()->live()->create(['event_id' => $this->event->id]);

    $response = $this->actingAs($this->user)
        ->get(route('event.sessions.show', [$this->event, $session]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Event/SessionDetail')
            ->has('session')
            ->has('participants')
        );
});

it('allows organizer to create sessions', function () {
    $organizer = $this->event->organizer;

    $response = $this->actingAs($organizer)
        ->post(route('event.sessions.store', $this->event), [
            'title' => 'New Session',
            'description' => 'A great session',
            'speaker' => 'Jane Doe',
            'room' => 'Room A',
            'starts_at' => now()->addHour()->toISOString(),
            'ends_at' => now()->addHours(2)->toISOString(),
        ]);

    $response->assertRedirect();
    expect(EventSession::where('title', 'New Session')->exists())->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=SessionControllerTest`
Expected: FAIL

- [ ] **Step 3: Create SessionController**

Run: `php artisan make:controller SessionController --no-interaction`

```php
// app/Http/Controllers/SessionController.php
<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionCheckIn;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    public function index(Request $request, Event $event): Response
    {
        $sessions = $event->sessions()
            ->orderBy('starts_at')
            ->get()
            ->map(fn (EventSession $s) => [
                'id' => $s->id,
                'title' => $s->title,
                'description' => $s->description,
                'speaker' => $s->speaker,
                'room' => $s->room,
                'starts_at' => $s->starts_at->toISOString(),
                'ends_at' => $s->ends_at->toISOString(),
                'is_live' => $s->isLive(),
                'qa_enabled' => $s->qa_enabled,
                'attendee_count' => $s->checkIns()->whereNull('checked_out_at')->count(),
            ]);

        return Inertia::render('Event/Sessions', [
            'event' => ['id' => $event->id, 'name' => $event->name, 'slug' => $event->slug],
            'sessions' => $sessions,
        ]);
    }

    public function show(Request $request, Event $event, EventSession $session): Response
    {
        $participants = SessionCheckIn::where('event_session_id', $session->id)
            ->whereNull('checked_out_at')
            ->with(['user' => fn ($q) => $q->with(['interestTags' => fn ($qt) => $qt->wherePivot('event_id', $event->id)])])
            ->get()
            ->map(fn ($checkIn) => [
                'id' => $checkIn->user->id,
                'name' => $checkIn->user->name,
                'participant_type' => $checkIn->user->events()->where('event_id', $event->id)->first()?->pivot?->participant_type,
                'interest_tags' => $checkIn->user->interestTags->pluck('name'),
            ]);

        $questions = $session->questions()
            ->with('user:id,name')
            ->withCount('votes')
            ->orderByDesc('votes_count')
            ->get();

        return Inertia::render('Event/SessionDetail', [
            'event' => ['id' => $event->id, 'name' => $event->name, 'slug' => $event->slug],
            'session' => [
                'id' => $session->id,
                'title' => $session->title,
                'description' => $session->description,
                'speaker' => $session->speaker,
                'room' => $session->room,
                'starts_at' => $session->starts_at->toISOString(),
                'ends_at' => $session->ends_at->toISOString(),
                'is_live' => $session->isLive(),
                'qa_enabled' => $session->qa_enabled,
                'reactions_enabled' => $session->reactions_enabled,
            ],
            'participants' => $participants,
            'questions' => $questions,
        ]);
    }

    public function store(Request $request, Event $event)
    {
        abort_unless($request->user()->id === $event->organizer_id, 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'speaker' => ['nullable', 'string', 'max:255'],
            'room' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);

        $event->sessions()->create($validated);

        return redirect()->route('event.sessions', $event);
    }
}
```

- [ ] **Step 4: Add routes**

Add to `routes/web.php` inside the auth middleware group:

```php
use App\Http\Controllers\SessionController;

Route::get('/event/{event:slug}/sessions', [SessionController::class, 'index'])->name('event.sessions');
Route::get('/event/{event:slug}/sessions/{session}', [SessionController::class, 'show'])->name('event.sessions.show');
Route::post('/event/{event:slug}/sessions', [SessionController::class, 'store'])->name('event.sessions.store');
```

- [ ] **Step 5: Run tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=SessionControllerTest`
Expected: All 3 tests PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/SessionController.php routes/web.php tests/Feature/Http/SessionControllerTest.php
git commit -m "feat: add SessionController with schedule, detail, and organizer CRUD"
```

---

## Task 2: Session Check-In Controller

**Files:**
- Create: `app/Http/Controllers/SessionCheckInController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Http/SessionCheckInControllerTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Http/SessionCheckInControllerTest.php
<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionCheckIn;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->user = User::factory()->create();
    $this->event->participants()->attach($this->user, ['participant_type' => 'physical', 'status' => 'available']);
    $this->session = EventSession::factory()->live()->create(['event_id' => $this->event->id]);
});

it('checks into a session', function () {
    $response = $this->actingAs($this->user)
        ->post(route('event.sessions.checkin', [$this->event, $this->session]));

    $response->assertOk();
    expect(SessionCheckIn::count())->toBe(1);

    $pivot = $this->user->events()->where('event_id', $this->event->id)->first()->pivot;
    expect($pivot->status)->toBe('in_session')
        ->and($pivot->context_badge)->toContain($this->session->title);
});

it('checks out of a session', function () {
    SessionCheckIn::create([
        'user_id' => $this->user->id,
        'event_session_id' => $this->session->id,
    ]);

    $response = $this->actingAs($this->user)
        ->delete(route('event.sessions.checkout', [$this->event, $this->session]));

    $response->assertOk();

    $pivot = $this->user->events()->where('event_id', $this->event->id)->first()->pivot;
    expect($pivot->status)->toBe('available')
        ->and($pivot->context_badge)->toBeNull();
});

it('auto-checks out of previous session when checking into new one', function () {
    $session2 = EventSession::factory()->live()->create(['event_id' => $this->event->id]);

    $this->actingAs($this->user)
        ->post(route('event.sessions.checkin', [$this->event, $this->session]));

    $this->actingAs($this->user)
        ->post(route('event.sessions.checkin', [$this->event, $session2]));

    expect(SessionCheckIn::whereNull('checked_out_at')->count())->toBe(1);
    expect(SessionCheckIn::whereNotNull('checked_out_at')->count())->toBe(1);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=SessionCheckInControllerTest`
Expected: FAIL

- [ ] **Step 3: Create SessionCheckInController**

Run: `php artisan make:controller SessionCheckInController --no-interaction`

```php
// app/Http/Controllers/SessionCheckInController.php
<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventSession;
use App\Services\PresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionCheckInController extends Controller
{
    public function store(Request $request, Event $event, EventSession $session, PresenceService $presenceService): JsonResponse
    {
        $presenceService->checkInToSession($request->user(), $event, $session);

        return response()->json(['message' => 'Checked in']);
    }

    public function destroy(Request $request, Event $event, EventSession $session, PresenceService $presenceService): JsonResponse
    {
        $presenceService->checkOutOfSession($request->user(), $event);

        return response()->json(['message' => 'Checked out']);
    }
}
```

- [ ] **Step 4: Add routes**

Add to `routes/web.php` inside the auth middleware group:

```php
use App\Http\Controllers\SessionCheckInController;

Route::post('/event/{event:slug}/sessions/{session}/checkin', [SessionCheckInController::class, 'store'])->name('event.sessions.checkin')->scopeBindings();
Route::delete('/event/{event:slug}/sessions/{session}/checkout', [SessionCheckInController::class, 'destroy'])->name('event.sessions.checkout')->scopeBindings();
```

- [ ] **Step 5: Run tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=SessionCheckInControllerTest`
Expected: All 3 tests PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/SessionCheckInController.php routes/web.php tests/Feature/Http/SessionCheckInControllerTest.php
git commit -m "feat: add session check-in/out controller with auto-checkout"
```

---

## Task 3: Session Reactions Controller

**Files:**
- Create: `app/Http/Controllers/SessionReactionController.php`
- Create: `app/Events/SessionReactionSent.php`
- Modify: `routes/web.php`
- Modify: `routes/channels.php`
- Create: `tests/Feature/Http/SessionReactionControllerTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Http/SessionReactionControllerTest.php
<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionReaction;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->user = User::factory()->create();
    $this->event->participants()->attach($this->user, ['participant_type' => 'physical', 'status' => 'available']);
    $this->session = EventSession::factory()->live()->create(['event_id' => $this->event->id]);
});

it('sends a reaction to a session', function () {
    $response = $this->actingAs($this->user)
        ->post(route('event.sessions.reactions.store', [$this->event, $this->session]), [
            'type' => 'lightbulb',
        ]);

    $response->assertOk();
    expect(SessionReaction::count())->toBe(1);
});

it('rejects invalid reaction types', function () {
    $response = $this->actingAs($this->user)
        ->post(route('event.sessions.reactions.store', [$this->event, $this->session]), [
            'type' => 'thumbsup',
        ]);

    $response->assertUnprocessable();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=SessionReactionControllerTest`
Expected: FAIL

- [ ] **Step 3: Create SessionReactionSent event**

```php
// app/Events/SessionReactionSent.php
<?php

namespace App\Events;

use App\Models\EventSession;
use App\Models\SessionReaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionReactionSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public EventSession $session,
        public SessionReaction $reaction,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("session.{$this->session->id}");
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->reaction->type,
            'user_id' => $this->reaction->user_id,
        ];
    }
}
```

- [ ] **Step 4: Create SessionReactionController**

Run: `php artisan make:controller SessionReactionController --no-interaction`

```php
// app/Http/Controllers/SessionReactionController.php
<?php

namespace App\Http\Controllers;

use App\Events\SessionReactionSent;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionReaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionReactionController extends Controller
{
    public function store(Request $request, Event $event, EventSession $session): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:lightbulb,clap,question,fire,think'],
        ]);

        $reaction = SessionReaction::create([
            'user_id' => $request->user()->id,
            'event_session_id' => $session->id,
            'type' => $validated['type'],
        ]);

        SessionReactionSent::dispatch($session, $reaction);

        return response()->json(['message' => 'Reaction sent']);
    }
}
```

- [ ] **Step 5: Add routes and channel**

Add to `routes/web.php`:

```php
use App\Http\Controllers\SessionReactionController;

Route::post('/event/{event:slug}/sessions/{session}/reactions', [SessionReactionController::class, 'store'])->name('event.sessions.reactions.store')->scopeBindings();
```

Add to `routes/channels.php`:

```php
Broadcast::channel('session.{sessionId}', function ($user, int $sessionId) {
    return \App\Models\SessionCheckIn::where('user_id', $user->id)
        ->where('event_session_id', $sessionId)
        ->whereNull('checked_out_at')
        ->exists();
});
```

- [ ] **Step 6: Run tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=SessionReactionControllerTest`
Expected: All 2 tests PASS

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/SessionReactionController.php app/Events/SessionReactionSent.php routes/web.php routes/channels.php tests/Feature/Http/SessionReactionControllerTest.php
git commit -m "feat: add session reactions with broadcasting"
```

---

## Task 4: Session Q&A Controller

**Files:**
- Create: `app/Http/Controllers/SessionQuestionController.php`
- Create: `app/Events/SessionQuestionPosted.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Http/SessionQuestionControllerTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Http/SessionQuestionControllerTest.php
<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionQuestion;
use App\Models\SessionQuestionVote;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->user = User::factory()->create();
    $this->event->participants()->attach($this->user, ['participant_type' => 'physical', 'status' => 'available']);
    $this->session = EventSession::factory()->live()->create(['event_id' => $this->event->id, 'qa_enabled' => true]);
});

it('submits a question', function () {
    $response = $this->actingAs($this->user)
        ->post(route('event.sessions.questions.store', [$this->event, $this->session]), [
            'body' => 'How does zero trust work at scale?',
        ]);

    $response->assertOk();
    expect(SessionQuestion::count())->toBe(1);
});

it('upvotes a question', function () {
    $question = SessionQuestion::factory()->create(['event_session_id' => $this->session->id]);

    $response = $this->actingAs($this->user)
        ->post(route('event.sessions.questions.vote', [$this->event, $this->session, $question]));

    $response->assertOk();
    expect(SessionQuestionVote::count())->toBe(1);
});

it('prevents duplicate votes', function () {
    $question = SessionQuestion::factory()->create(['event_session_id' => $this->session->id]);

    $this->actingAs($this->user)
        ->post(route('event.sessions.questions.vote', [$this->event, $this->session, $question]));

    $response = $this->actingAs($this->user)
        ->post(route('event.sessions.questions.vote', [$this->event, $this->session, $question]));

    $response->assertStatus(409);
});

it('rejects questions when Q&A is disabled', function () {
    $this->session->update(['qa_enabled' => false]);

    $response = $this->actingAs($this->user)
        ->post(route('event.sessions.questions.store', [$this->event, $this->session]), [
            'body' => 'A question',
        ]);

    $response->assertForbidden();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=SessionQuestionControllerTest`
Expected: FAIL

- [ ] **Step 3: Create SessionQuestionPosted event**

```php
// app/Events/SessionQuestionPosted.php
<?php

namespace App\Events;

use App\Models\EventSession;
use App\Models\SessionQuestion;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionQuestionPosted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public EventSession $session,
        public SessionQuestion $question,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("session.{$this->session->id}");
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->question->id,
            'body' => $this->question->body,
            'user_name' => $this->question->user->name,
            'user_id' => $this->question->user_id,
            'votes_count' => 0,
        ];
    }
}
```

- [ ] **Step 4: Create SessionQuestionController**

Run: `php artisan make:controller SessionQuestionController --no-interaction`

```php
// app/Http/Controllers/SessionQuestionController.php
<?php

namespace App\Http\Controllers;

use App\Events\SessionQuestionPosted;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionQuestion;
use App\Models\SessionQuestionVote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionQuestionController extends Controller
{
    public function store(Request $request, Event $event, EventSession $session): JsonResponse
    {
        abort_unless($session->qa_enabled, 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:500'],
        ]);

        $question = SessionQuestion::create([
            'user_id' => $request->user()->id,
            'event_session_id' => $session->id,
            'body' => $validated['body'],
        ]);

        SessionQuestionPosted::dispatch($session, $question);

        return response()->json(['message' => 'Question submitted']);
    }

    public function vote(Request $request, Event $event, EventSession $session, SessionQuestion $question): JsonResponse
    {
        $exists = SessionQuestionVote::where('session_question_id', $question->id)
            ->where('user_id', $request->user()->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Already voted'], 409);
        }

        SessionQuestionVote::create([
            'session_question_id' => $question->id,
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Vote recorded']);
    }
}
```

- [ ] **Step 5: Add routes**

Add to `routes/web.php`:

```php
use App\Http\Controllers\SessionQuestionController;

Route::post('/event/{event:slug}/sessions/{session}/questions', [SessionQuestionController::class, 'store'])->name('event.sessions.questions.store')->scopeBindings();
Route::post('/event/{event:slug}/sessions/{session}/questions/{question}/vote', [SessionQuestionController::class, 'vote'])->name('event.sessions.questions.vote')->scopeBindings();
```

- [ ] **Step 6: Run tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=SessionQuestionControllerTest`
Expected: All 4 tests PASS

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/SessionQuestionController.php app/Events/SessionQuestionPosted.php routes/web.php tests/Feature/Http/SessionQuestionControllerTest.php
git commit -m "feat: add session Q&A with questions, upvotes, and broadcasting"
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
