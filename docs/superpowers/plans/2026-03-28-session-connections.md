# Session Connections — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Transform sessions from passive broadcasts into connection engines — threaded Q&A, reaction-driven matching, organizer moderation, and post-session connection screen.

**Architecture:** Extends existing session infrastructure. Q&A threading follows the booth thread pattern (BoothThreadReply). Post-session connections reuse `SuggestionService` with a new `'session_affinity'` trigger. `SessionEndedJob` pre-computes engagement edges at session close. Client-side reaction clustering avoids server overhead. Channel auth expanded for organizers and post-session grace window.

**Tech Stack:** Laravel 13, Reverb, Inertia v3, Vue 3, Pest v4, Wayfinder

**Depends on:** Plans 1-5 (all session infrastructure must be in place)

**Spec:** `docs/superpowers/specs/2026-03-28-session-connections-design.md`

## TDD Standard

- Start each task with failing tests before implementation.
- Cover edge cases: unauthorized access, duplicate votes, hidden questions, post-session expiry, cross-world reranking.
- For Inertia endpoints, add `assertInertia` coverage for component props.
- Use factories for all test models.

---

## File Structure

### Migrations
```
database/migrations/YYYY_MM_DD_HHMMSS_add_speaker_user_id_to_event_sessions_table.php
database/migrations/YYYY_MM_DD_HHMMSS_add_moderation_columns_to_session_questions_table.php
database/migrations/YYYY_MM_DD_HHMMSS_create_session_question_replies_table.php
database/migrations/YYYY_MM_DD_HHMMSS_create_session_question_reply_votes_table.php
database/migrations/YYYY_MM_DD_HHMMSS_create_session_engagement_edges_table.php
```

### Models
```
app/Models/SessionQuestionReply.php
app/Models/SessionQuestionReplyVote.php
app/Models/SessionEngagementEdge.php
```

### Controllers
```
app/Http/Controllers/SessionQuestionReplyController.php
app/Http/Controllers/SessionModerateController.php
```

### Events
```
app/Events/SessionQuestionReplyPosted.php
app/Events/SessionQuestionPinned.php
app/Events/SessionEnded.php
```

### Jobs
```
app/Jobs/SessionEndedJob.php
```

### Services (modified)
```
app/Services/MatchingService.php          — add session_affinity weight
app/Services/SuggestionService.php        — add session_affinity trigger
app/Services/SessionEngagementService.php — new, computes engagement edges
```

### Vue Pages
```
resources/js/pages/Event/SessionModerate.vue
resources/js/pages/Event/PostSessionConnections.vue
resources/js/pages/Event/SessionDetail.vue  — modified (threads, cluster badge)
```

### Other Modified Files
```
app/Models/EventSession.php       — add speaker relation
app/Models/SessionQuestion.php    — add moderation fields, replies relation
routes/channels.php               — expand session channel auth
routes/web.php                    — add new routes
app/Console/Kernel.php            — schedule SessionEndedJob
```

### Tests
```
tests/Feature/SessionQuestionReplyTest.php
tests/Feature/SessionModerateTest.php
tests/Feature/SessionEngagementEdgeTest.php
tests/Feature/SessionAffinityMatchingTest.php
tests/Feature/PostSessionConnectionTest.php
tests/Feature/SessionChannelAuthTest.php
```

---

## Task 1: Add speaker_user_id to EventSession

**Files:**
- Create: `database/migrations/..._add_speaker_user_id_to_event_sessions_table.php`
- Modify: `app/Models/EventSession.php`
- Test: `tests/Feature/Models/EventSessionTest.php`

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/Models/EventSessionTest.php`:

```php
it('has a speaker user relationship', function () {
    $speaker = User::factory()->create();
    $session = EventSession::factory()->create(['speaker_user_id' => $speaker->id]);

    expect($session->speakerUser)->toBeInstanceOf(User::class);
    expect($session->speakerUser->id)->toBe($speaker->id);
});

it('allows null speaker_user_id', function () {
    $session = EventSession::factory()->create(['speaker_user_id' => null]);

    expect($session->speakerUser)->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter="has a speaker user relationship"`
Expected: FAIL — column speaker_user_id doesn't exist

- [ ] **Step 3: Create migration**

Run: `php artisan make:migration add_speaker_user_id_to_event_sessions_table --no-interaction`

Edit the migration:

```php
public function up(): void
{
    Schema::table('event_sessions', function (Blueprint $table) {
        $table->foreignId('speaker_user_id')->nullable()->after('speaker')->constrained('users')->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('event_sessions', function (Blueprint $table) {
        $table->dropConstrainedForeignId('speaker_user_id');
    });
}
```

- [ ] **Step 4: Add relationship to EventSession model**

In `app/Models/EventSession.php`, add:

```php
public function speakerUser(): BelongsTo
{
    return $this->belongsTo(User::class, 'speaker_user_id');
}
```

Add the import if not present: `use Illuminate\Database\Eloquent\Relations\BelongsTo;`

- [ ] **Step 5: Run migration and test**

Run: `php artisan migrate --no-interaction && php artisan test --compact --filter="has a speaker user relationship"`
Expected: PASS

- [ ] **Step 6: Lint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A && git commit -m "feat: add speaker_user_id FK to event_sessions"
```

---

## Task 2: Add moderation columns to session_questions

**Files:**
- Create: `database/migrations/..._add_moderation_columns_to_session_questions_table.php`
- Modify: `app/Models/SessionQuestion.php`

- [ ] **Step 1: Write the failing test**

Add to a new file `tests/Feature/SessionModerateTest.php`:

```php
<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionQuestion;
use App\Models\User;

it('session question has moderation fields', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $session = EventSession::factory()->for($event)->live()->create();
    $question = SessionQuestion::factory()->create([
        'event_session_id' => $session->id,
        'is_pinned' => true,
        'is_hidden' => false,
        'answered_by' => $organizer->id,
    ]);

    expect($question->is_pinned)->toBeTrue();
    expect($question->is_hidden)->toBeFalse();
    expect($question->answered_by)->toBe($organizer->id);
    expect($question->answeredByUser)->toBeInstanceOf(User::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter="session question has moderation fields"`
Expected: FAIL — column is_pinned doesn't exist

- [ ] **Step 3: Create migration**

Run: `php artisan make:migration add_moderation_columns_to_session_questions_table --no-interaction`

```php
public function up(): void
{
    Schema::table('session_questions', function (Blueprint $table) {
        $table->boolean('is_pinned')->default(false)->after('is_answered');
        $table->boolean('is_hidden')->default(false)->after('is_pinned');
        $table->foreignId('answered_by')->nullable()->after('is_hidden')->constrained('users')->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('session_questions', function (Blueprint $table) {
        $table->dropConstrainedForeignId('answered_by');
        $table->dropColumn(['is_pinned', 'is_hidden']);
    });
}
```

- [ ] **Step 4: Update SessionQuestion model**

In `app/Models/SessionQuestion.php`, add to `$casts`:

```php
protected function casts(): array
{
    return [
        'is_answered' => 'boolean',
        'is_pinned' => 'boolean',
        'is_hidden' => 'boolean',
    ];
}
```

Add relationship:

```php
public function answeredByUser(): BelongsTo
{
    return $this->belongsTo(User::class, 'answered_by');
}
```

- [ ] **Step 5: Run migration and test**

Run: `php artisan migrate --no-interaction && php artisan test --compact --filter="session question has moderation fields"`
Expected: PASS

- [ ] **Step 6: Lint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A && git commit -m "feat: add moderation columns to session_questions"
```

---

## Task 3: Create SessionQuestionReply model and migration

**Files:**
- Create: `database/migrations/..._create_session_question_replies_table.php`
- Create: `app/Models/SessionQuestionReply.php`
- Modify: `app/Models/SessionQuestion.php` (add replies relation)

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/SessionQuestionReplyTest.php`:

```php
<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionQuestion;
use App\Models\SessionQuestionReply;
use App\Models\User;

it('creates a reply on a session question', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->live()->create();
    $author = User::factory()->create();
    $question = SessionQuestion::factory()->create(['event_session_id' => $session->id]);

    $reply = SessionQuestionReply::create([
        'session_question_id' => $question->id,
        'user_id' => $author->id,
        'body' => 'Great question, here is my take on it.',
    ]);

    expect($reply)->toBeInstanceOf(SessionQuestionReply::class);
    expect($reply->question->id)->toBe($question->id);
    expect($reply->user->id)->toBe($author->id);
    expect($question->replies)->toHaveCount(1);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter="creates a reply on a session question"`
Expected: FAIL — class SessionQuestionReply not found

- [ ] **Step 3: Create migration**

Run: `php artisan make:migration create_session_question_replies_table --no-interaction`

```php
public function up(): void
{
    Schema::create('session_question_replies', function (Blueprint $table) {
        $table->id();
        $table->foreignId('session_question_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->text('body');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('session_question_replies');
}
```

- [ ] **Step 4: Create model**

Run: `php artisan make:class App/Models/SessionQuestionReply --no-interaction`

Replace contents:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessionQuestionReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_question_id',
        'user_id',
        'body',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(SessionQuestion::class, 'session_question_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(SessionQuestionReplyVote::class);
    }
}
```

- [ ] **Step 5: Add replies relation to SessionQuestion**

In `app/Models/SessionQuestion.php`, add:

```php
public function replies(): HasMany
{
    return $this->hasMany(SessionQuestionReply::class);
}
```

- [ ] **Step 6: Run migration and test**

Run: `php artisan migrate --no-interaction && php artisan test --compact --filter="creates a reply on a session question"`
Expected: PASS

- [ ] **Step 7: Lint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A && git commit -m "feat: add SessionQuestionReply model and migration"
```

---

## Task 4: Create SessionQuestionReplyVote model and migration

**Files:**
- Create: `database/migrations/..._create_session_question_reply_votes_table.php`
- Create: `app/Models/SessionQuestionReplyVote.php`

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/SessionQuestionReplyTest.php`:

```php
use App\Models\SessionQuestionReplyVote;

it('prevents duplicate votes on a reply', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->live()->create();
    $question = SessionQuestion::factory()->create(['event_session_id' => $session->id]);
    $reply = SessionQuestionReply::create([
        'session_question_id' => $question->id,
        'user_id' => User::factory()->create()->id,
        'body' => 'A reply',
    ]);
    $voter = User::factory()->create();

    SessionQuestionReplyVote::create([
        'session_question_reply_id' => $reply->id,
        'user_id' => $voter->id,
    ]);

    expect(fn () => SessionQuestionReplyVote::create([
        'session_question_reply_id' => $reply->id,
        'user_id' => $voter->id,
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter="prevents duplicate votes on a reply"`
Expected: FAIL — class not found

- [ ] **Step 3: Create migration**

Run: `php artisan make:migration create_session_question_reply_votes_table --no-interaction`

```php
public function up(): void
{
    Schema::create('session_question_reply_votes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('session_question_reply_id')->constrained('session_question_replies')->cascadeOnDelete();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->timestamps();

        $table->unique(['session_question_reply_id', 'user_id'], 'reply_vote_unique');
    });
}

public function down(): void
{
    Schema::dropIfExists('session_question_reply_votes');
}
```

- [ ] **Step 4: Create model**

Run: `php artisan make:class App/Models/SessionQuestionReplyVote --no-interaction`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionQuestionReplyVote extends Model
{
    protected $fillable = [
        'session_question_reply_id',
        'user_id',
    ];

    public function reply(): BelongsTo
    {
        return $this->belongsTo(SessionQuestionReply::class, 'session_question_reply_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 5: Run migration and test**

Run: `php artisan migrate --no-interaction && php artisan test --compact --filter="prevents duplicate votes on a reply"`
Expected: PASS

- [ ] **Step 6: Lint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A && git commit -m "feat: add SessionQuestionReplyVote model and migration"
```

---

## Task 5: Create SessionQuestionReplyController with reply and vote endpoints

**Files:**
- Create: `app/Http/Controllers/SessionQuestionReplyController.php`
- Create: `app/Events/SessionQuestionReplyPosted.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Write the failing tests**

Add to `tests/Feature/SessionQuestionReplyTest.php`:

```php
use App\Models\SessionCheckIn;

it('allows a checked-in participant to reply to a question', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->live()->create(['qa_enabled' => true]);
    $user = User::factory()->create();
    $event->participants()->attach($user, ['participant_type' => 'physical', 'status' => 'in_session']);
    SessionCheckIn::create(['user_id' => $user->id, 'event_session_id' => $session->id]);
    $question = SessionQuestion::factory()->create(['event_session_id' => $session->id]);

    $response = $this->actingAs($user)->postJson(
        route('event.sessions.questions.replies.store', [$event, $session, $question]),
        ['body' => 'Here is my answer to your question.']
    );

    $response->assertOk();
    expect($question->replies()->count())->toBe(1);
});

it('rejects reply from non-checked-in user', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->live()->create(['qa_enabled' => true]);
    $user = User::factory()->create();
    $event->participants()->attach($user, ['participant_type' => 'physical', 'status' => 'available']);
    $question = SessionQuestion::factory()->create(['event_session_id' => $session->id]);

    $response = $this->actingAs($user)->postJson(
        route('event.sessions.questions.replies.store', [$event, $session, $question]),
        ['body' => 'Should not work']
    );

    $response->assertForbidden();
});

it('allows a checked-in participant to vote on a reply', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->live()->create(['qa_enabled' => true]);
    $voter = User::factory()->create();
    $event->participants()->attach($voter, ['participant_type' => 'remote', 'status' => 'in_session']);
    SessionCheckIn::create(['user_id' => $voter->id, 'event_session_id' => $session->id]);
    $question = SessionQuestion::factory()->create(['event_session_id' => $session->id]);
    $reply = SessionQuestionReply::create([
        'session_question_id' => $question->id,
        'user_id' => User::factory()->create()->id,
        'body' => 'A reply',
    ]);

    $response = $this->actingAs($voter)->postJson(
        route('event.sessions.questions.replies.vote', [$event, $session, $question, $reply])
    );

    $response->assertOk();
    expect($reply->votes()->count())->toBe(1);
});

it('returns 409 for duplicate reply vote', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->live()->create(['qa_enabled' => true]);
    $voter = User::factory()->create();
    $event->participants()->attach($voter, ['participant_type' => 'remote', 'status' => 'in_session']);
    SessionCheckIn::create(['user_id' => $voter->id, 'event_session_id' => $session->id]);
    $question = SessionQuestion::factory()->create(['event_session_id' => $session->id]);
    $reply = SessionQuestionReply::create([
        'session_question_id' => $question->id,
        'user_id' => User::factory()->create()->id,
        'body' => 'A reply',
    ]);
    SessionQuestionReplyVote::create(['session_question_reply_id' => $reply->id, 'user_id' => $voter->id]);

    $response = $this->actingAs($voter)->postJson(
        route('event.sessions.questions.replies.vote', [$event, $session, $question, $reply])
    );

    $response->assertConflict();
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter="allows a checked-in participant to reply"`
Expected: FAIL — route not defined

- [ ] **Step 3: Create the broadcast event**

Run: `php artisan make:event SessionQuestionReplyPosted --no-interaction`

```php
<?php

namespace App\Events;

use App\Models\EventSession;
use App\Models\SessionQuestionReply;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionQuestionReplyPosted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public EventSession $session,
        public SessionQuestionReply $reply,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("session.{$this->session->id}");
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->reply->id,
            'session_question_id' => $this->reply->session_question_id,
            'body' => $this->reply->body,
            'user_id' => $this->reply->user_id,
            'user_name' => $this->reply->user->name,
            'votes_count' => 0,
        ];
    }
}
```

- [ ] **Step 4: Create the controller**

Run: `php artisan make:controller SessionQuestionReplyController --no-interaction`

```php
<?php

namespace App\Http\Controllers;

use App\Events\SessionQuestionReplyPosted;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionQuestion;
use App\Models\SessionQuestionReply;
use App\Models\SessionQuestionReplyVote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionQuestionReplyController extends Controller
{
    public function store(Request $request, Event $event, EventSession $session, SessionQuestion $question): JsonResponse
    {
        abort_unless($session->hasActiveCheckInFor($request->user()), 403);
        abort_unless($session->qa_enabled, 403);
        abort_unless($session->canInteract(), 422, 'Session has ended');

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:500'],
        ]);

        $reply = SessionQuestionReply::create([
            'session_question_id' => $question->id,
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        SessionQuestionReplyPosted::dispatch($session, $reply);

        return response()->json(['message' => 'Reply posted']);
    }

    public function vote(Request $request, Event $event, EventSession $session, SessionQuestion $question, SessionQuestionReply $reply): JsonResponse
    {
        abort_unless($session->hasActiveCheckInFor($request->user()), 403);
        abort_unless($session->qa_enabled, 403);

        $exists = SessionQuestionReplyVote::where('session_question_reply_id', $reply->id)
            ->where('user_id', $request->user()->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Already voted'], 409);
        }

        SessionQuestionReplyVote::create([
            'session_question_reply_id' => $reply->id,
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Vote recorded']);
    }
}
```

- [ ] **Step 5: Add routes**

In `routes/web.php`, add after the existing session question vote route:

```php
Route::post('/event/{event:slug}/sessions/{session}/questions/{question}/replies', [SessionQuestionReplyController::class, 'store'])
    ->name('event.sessions.questions.replies.store')->scopeBindings();
Route::post('/event/{event:slug}/sessions/{session}/questions/{question}/replies/{reply}/vote', [SessionQuestionReplyController::class, 'vote'])
    ->name('event.sessions.questions.replies.vote')->scopeBindings();
```

Add import: `use App\Http\Controllers\SessionQuestionReplyController;`

- [ ] **Step 6: Run tests**

Run: `php artisan test --compact --filter="SessionQuestionReplyTest"`
Expected: ALL PASS

- [ ] **Step 7: Lint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A && git commit -m "feat: add session question reply and vote endpoints"
```

---

## Task 6: Create SessionModerateController (pin, hide, mark answered)

**Files:**
- Create: `app/Http/Controllers/SessionModerateController.php`
- Create: `app/Events/SessionQuestionPinned.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Write the failing tests**

Add to `tests/Feature/SessionModerateTest.php`:

```php
use App\Models\SessionCheckIn;
use App\Models\SessionQuestion;

it('allows organizer to pin a question', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $session = EventSession::factory()->for($event)->live()->create();
    $question = SessionQuestion::factory()->create(['event_session_id' => $session->id]);

    $response = $this->actingAs($organizer)->postJson(
        route('event.sessions.questions.pin', [$event, $session, $question])
    );

    $response->assertOk();
    expect($question->fresh()->is_pinned)->toBeTrue();
});

it('allows organizer to unpin a question', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $session = EventSession::factory()->for($event)->live()->create();
    $question = SessionQuestion::factory()->create([
        'event_session_id' => $session->id,
        'is_pinned' => true,
    ]);

    $response = $this->actingAs($organizer)->postJson(
        route('event.sessions.questions.pin', [$event, $session, $question])
    );

    $response->assertOk();
    expect($question->fresh()->is_pinned)->toBeFalse();
});

it('allows organizer to hide a question', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $session = EventSession::factory()->for($event)->live()->create();
    $question = SessionQuestion::factory()->create(['event_session_id' => $session->id]);

    $response = $this->actingAs($organizer)->postJson(
        route('event.sessions.questions.hide', [$event, $session, $question])
    );

    $response->assertOk();
    expect($question->fresh()->is_hidden)->toBeTrue();
});

it('allows organizer to mark a question as answered', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $session = EventSession::factory()->for($event)->live()->create();
    $question = SessionQuestion::factory()->create(['event_session_id' => $session->id]);

    $response = $this->actingAs($organizer)->postJson(
        route('event.sessions.questions.answer', [$event, $session, $question])
    );

    $response->assertOk();
    expect($question->fresh()->is_answered)->toBeTrue();
    expect($question->fresh()->answered_by)->toBe($organizer->id);
});

it('allows speaker to mark a question as answered', function () {
    $speaker = User::factory()->create();
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->live()->create(['speaker_user_id' => $speaker->id]);
    $question = SessionQuestion::factory()->create(['event_session_id' => $session->id]);

    $response = $this->actingAs($speaker)->postJson(
        route('event.sessions.questions.answer', [$event, $session, $question])
    );

    $response->assertOk();
    expect($question->fresh()->is_answered)->toBeTrue();
});

it('rejects moderation from non-organizer non-speaker', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->live()->create();
    $question = SessionQuestion::factory()->create(['event_session_id' => $session->id]);

    $response = $this->actingAs($user)->postJson(
        route('event.sessions.questions.pin', [$event, $session, $question])
    );

    $response->assertForbidden();
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter="allows organizer to pin"`
Expected: FAIL — route not defined

- [ ] **Step 3: Create SessionQuestionPinned event**

Run: `php artisan make:event SessionQuestionPinned --no-interaction`

```php
<?php

namespace App\Events;

use App\Models\EventSession;
use App\Models\SessionQuestion;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionQuestionPinned implements ShouldBroadcast
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
            'question_id' => $this->question->id,
            'is_pinned' => $this->question->is_pinned,
        ];
    }
}
```

- [ ] **Step 4: Create the controller**

Run: `php artisan make:controller SessionModerateController --no-interaction`

```php
<?php

namespace App\Http\Controllers;

use App\Events\SessionQuestionPinned;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionModerateController extends Controller
{
    public function pin(Request $request, Event $event, EventSession $session, SessionQuestion $question): JsonResponse
    {
        $this->authorizeModeration($request, $event, $session);

        $question->update(['is_pinned' => ! $question->is_pinned]);

        SessionQuestionPinned::dispatch($session, $question->fresh());

        return response()->json(['message' => $question->fresh()->is_pinned ? 'Question pinned' : 'Question unpinned']);
    }

    public function hide(Request $request, Event $event, EventSession $session, SessionQuestion $question): JsonResponse
    {
        $this->authorizeModeration($request, $event, $session);

        $question->update(['is_hidden' => ! $question->is_hidden]);

        return response()->json(['message' => $question->fresh()->is_hidden ? 'Question hidden' : 'Question visible']);
    }

    public function answer(Request $request, Event $event, EventSession $session, SessionQuestion $question): JsonResponse
    {
        $this->authorizeModeration($request, $event, $session);

        $isAnswered = ! $question->is_answered;

        $question->update([
            'is_answered' => $isAnswered,
            'answered_by' => $isAnswered ? $request->user()->id : null,
        ]);

        return response()->json(['message' => $isAnswered ? 'Marked as answered' : 'Unmarked as answered']);
    }

    private function authorizeModeration(Request $request, Event $event, EventSession $session): void
    {
        $isOrganizer = $event->organizer_id === $request->user()->id;
        $isSpeaker = $session->speaker_user_id === $request->user()->id;

        abort_unless($isOrganizer || $isSpeaker, 403);
    }
}
```

- [ ] **Step 5: Add routes**

In `routes/web.php`:

```php
Route::post('/event/{event:slug}/sessions/{session}/questions/{question}/pin', [SessionModerateController::class, 'pin'])
    ->name('event.sessions.questions.pin')->scopeBindings();
Route::post('/event/{event:slug}/sessions/{session}/questions/{question}/hide', [SessionModerateController::class, 'hide'])
    ->name('event.sessions.questions.hide')->scopeBindings();
Route::post('/event/{event:slug}/sessions/{session}/questions/{question}/answer', [SessionModerateController::class, 'answer'])
    ->name('event.sessions.questions.answer')->scopeBindings();
```

Add import: `use App\Http\Controllers\SessionModerateController;`

- [ ] **Step 6: Run tests**

Run: `php artisan test --compact --filter="SessionModerateTest"`
Expected: ALL PASS

- [ ] **Step 7: Lint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A && git commit -m "feat: add session moderation endpoints (pin, hide, answer)"
```

---

## Task 7: Expand session channel authorization

**Files:**
- Modify: `routes/channels.php`
- Test: `tests/Feature/SessionChannelAuthTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/SessionChannelAuthTest.php`:

```php
<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionCheckIn;
use App\Models\User;
use Illuminate\Support\Carbon;

it('authorizes organizer on session channel without check-in', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $session = EventSession::factory()->for($event)->live()->create();

    $this->actingAs($organizer);

    $response = $this->postJson('/broadcasting/auth', [
        'channel_name' => "private-session.{$session->id}",
    ]);

    $response->assertOk();
});

it('authorizes checked-in participant on session channel', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->live()->create();
    $event->participants()->attach($user, ['participant_type' => 'physical', 'status' => 'in_session']);
    SessionCheckIn::create(['user_id' => $user->id, 'event_session_id' => $session->id]);

    $this->actingAs($user);

    $response = $this->postJson('/broadcasting/auth', [
        'channel_name' => "private-session.{$session->id}",
    ]);

    $response->assertOk();
});

it('authorizes post-session participant within 15-minute grace window', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->create([
        'starts_at' => now()->subHours(2),
        'ends_at' => now()->subMinutes(5),
    ]);
    SessionCheckIn::create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
        'checked_out_at' => now()->subMinutes(5),
    ]);

    $this->actingAs($user);

    $response = $this->postJson('/broadcasting/auth', [
        'channel_name' => "private-session.{$session->id}",
    ]);

    $response->assertOk();
});

it('rejects participant after 15-minute grace window', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->create([
        'starts_at' => now()->subHours(3),
        'ends_at' => now()->subMinutes(20),
    ]);
    SessionCheckIn::create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
        'checked_out_at' => now()->subMinutes(20),
    ]);

    $this->actingAs($user);

    $response = $this->postJson('/broadcasting/auth', [
        'channel_name' => "private-session.{$session->id}",
    ]);

    $response->assertForbidden();
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter="SessionChannelAuthTest"`
Expected: FAIL — organizer test fails (current auth only checks active check-in)

- [ ] **Step 3: Update channel authorization**

In `routes/channels.php`, replace the session channel callback:

```php
Broadcast::channel('session.{sessionId}', function ($user, int $sessionId) {
    $session = EventSession::find($sessionId);

    if (! $session) {
        return false;
    }

    // Organizer always has access
    if ($session->event->organizer_id === $user->id) {
        return true;
    }

    // Active check-in
    $hasActiveCheckIn = SessionCheckIn::where('user_id', $user->id)
        ->where('event_session_id', $sessionId)
        ->whereNull('checked_out_at')
        ->exists();

    if ($hasActiveCheckIn) {
        return true;
    }

    // Post-session grace: attended this session and session ended within 15 minutes
    if ($session->ends_at && $session->ends_at->isPast() && $session->ends_at->diffInMinutes(now()) <= 15) {
        return SessionCheckIn::where('user_id', $user->id)
            ->where('event_session_id', $sessionId)
            ->exists();
    }

    return false;
});
```

Add imports at top of file if not present:

```php
use App\Models\EventSession;
use App\Models\SessionCheckIn;
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --compact --filter="SessionChannelAuthTest"`
Expected: ALL PASS

- [ ] **Step 5: Lint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A && git commit -m "feat: expand session channel auth for organizers and post-session grace"
```

---

## Task 8: Create SessionEngagementEdge model, migration, and service

**Files:**
- Create: `database/migrations/..._create_session_engagement_edges_table.php`
- Create: `app/Models/SessionEngagementEdge.php`
- Create: `app/Services/SessionEngagementService.php`
- Test: `tests/Feature/SessionEngagementEdgeTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/SessionEngagementEdgeTest.php`:

```php
<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionCheckIn;
use App\Models\SessionEngagementEdge;
use App\Models\SessionQuestion;
use App\Models\SessionQuestionReply;
use App\Models\SessionReaction;
use App\Models\User;
use App\Services\SessionEngagementService;

it('computes reaction sync score for users who reacted in same time windows', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->create([
        'starts_at' => now()->subHour(),
        'ends_at' => now()->subMinutes(5),
    ]);

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    SessionCheckIn::create(['user_id' => $userA->id, 'event_session_id' => $session->id, 'checked_out_at' => now()->subMinutes(5)]);
    SessionCheckIn::create(['user_id' => $userB->id, 'event_session_id' => $session->id, 'checked_out_at' => now()->subMinutes(5)]);

    // Both react in same 30-sec window
    $baseTime = now()->subMinutes(30);
    SessionReaction::create(['user_id' => $userA->id, 'event_session_id' => $session->id, 'type' => 'fire', 'created_at' => $baseTime]);
    SessionReaction::create(['user_id' => $userB->id, 'event_session_id' => $session->id, 'type' => 'clap', 'created_at' => $baseTime->copy()->addSeconds(10)]);

    // User A reacts alone in another window
    SessionReaction::create(['user_id' => $userA->id, 'event_session_id' => $session->id, 'type' => 'think', 'created_at' => $baseTime->copy()->addMinutes(5)]);

    $service = app(SessionEngagementService::class);
    $service->computeForSession($session);

    $edge = SessionEngagementEdge::where('event_session_id', $session->id)
        ->where('user_a_id', min($userA->id, $userB->id))
        ->where('user_b_id', max($userA->id, $userB->id))
        ->first();

    expect($edge)->not->toBeNull();
    expect($edge->reaction_sync_score)->toBeGreaterThan(0.0);
    expect($edge->reaction_sync_score)->toBeLessThanOrEqual(1.0);
});

it('computes qa interaction score for users who replied to each other', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->create([
        'starts_at' => now()->subHour(),
        'ends_at' => now()->subMinutes(5),
    ]);

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    SessionCheckIn::create(['user_id' => $userA->id, 'event_session_id' => $session->id, 'checked_out_at' => now()->subMinutes(5)]);
    SessionCheckIn::create(['user_id' => $userB->id, 'event_session_id' => $session->id, 'checked_out_at' => now()->subMinutes(5)]);

    // User A asks, User B replies
    $question = SessionQuestion::factory()->create([
        'event_session_id' => $session->id,
        'user_id' => $userA->id,
    ]);
    SessionQuestionReply::create([
        'session_question_id' => $question->id,
        'user_id' => $userB->id,
        'body' => 'Great point!',
    ]);

    $service = app(SessionEngagementService::class);
    $service->computeForSession($session);

    $edge = SessionEngagementEdge::where('event_session_id', $session->id)
        ->where('user_a_id', min($userA->id, $userB->id))
        ->where('user_b_id', max($userA->id, $userB->id))
        ->first();

    expect($edge)->not->toBeNull();
    expect($edge->qa_interaction_score)->toBeGreaterThan(0.0);
});

it('creates no edges when users have no shared engagement', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->create([
        'starts_at' => now()->subHour(),
        'ends_at' => now()->subMinutes(5),
    ]);

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    SessionCheckIn::create(['user_id' => $userA->id, 'event_session_id' => $session->id, 'checked_out_at' => now()->subMinutes(5)]);
    SessionCheckIn::create(['user_id' => $userB->id, 'event_session_id' => $session->id, 'checked_out_at' => now()->subMinutes(5)]);

    // No reactions or Q&A interactions

    $service = app(SessionEngagementService::class);
    $service->computeForSession($session);

    $count = SessionEngagementEdge::where('event_session_id', $session->id)->count();
    expect($count)->toBe(0);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter="computes reaction sync score"`
Expected: FAIL — class not found

- [ ] **Step 3: Create migration**

Run: `php artisan make:migration create_session_engagement_edges_table --no-interaction`

```php
public function up(): void
{
    Schema::create('session_engagement_edges', function (Blueprint $table) {
        $table->id();
        $table->foreignId('event_session_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_a_id')->constrained('users')->cascadeOnDelete();
        $table->foreignId('user_b_id')->constrained('users')->cascadeOnDelete();
        $table->float('reaction_sync_score')->default(0);
        $table->float('qa_interaction_score')->default(0);
        $table->timestamps();

        $table->unique(['event_session_id', 'user_a_id', 'user_b_id'], 'engagement_edge_unique');
    });
}

public function down(): void
{
    Schema::dropIfExists('session_engagement_edges');
}
```

- [ ] **Step 4: Create model**

Run: `php artisan make:class App/Models/SessionEngagementEdge --no-interaction`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionEngagementEdge extends Model
{
    protected $fillable = [
        'event_session_id',
        'user_a_id',
        'user_b_id',
        'reaction_sync_score',
        'qa_interaction_score',
    ];

    protected function casts(): array
    {
        return [
            'reaction_sync_score' => 'float',
            'qa_interaction_score' => 'float',
        ];
    }

    public function eventSession(): BelongsTo
    {
        return $this->belongsTo(EventSession::class);
    }

    public function userA(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_a_id');
    }

    public function userB(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_b_id');
    }

    public function score(): float
    {
        return ($this->reaction_sync_score * 0.6) + ($this->qa_interaction_score * 0.4);
    }
}
```

- [ ] **Step 5: Create SessionEngagementService**

Run: `php artisan make:class App/Services/SessionEngagementService --no-interaction`

```php
<?php

namespace App\Services;

use App\Models\EventSession;
use App\Models\SessionCheckIn;
use App\Models\SessionEngagementEdge;
use App\Models\SessionQuestion;
use App\Models\SessionQuestionReply;
use App\Models\SessionReaction;
use Illuminate\Support\Collection;

class SessionEngagementService
{
    private const WINDOW_SECONDS = 30;

    public function computeForSession(EventSession $session): void
    {
        $attendeeIds = SessionCheckIn::where('event_session_id', $session->id)
            ->pluck('user_id')
            ->unique()
            ->values();

        if ($attendeeIds->count() < 2) {
            return;
        }

        $reactionFingerprints = $this->buildReactionFingerprints($session, $attendeeIds);
        $qaEdges = $this->buildQaInteractionEdges($session, $attendeeIds);

        // Generate all pairs
        $attendeeArray = $attendeeIds->toArray();
        $pairCount = count($attendeeArray);

        for ($i = 0; $i < $pairCount; $i++) {
            for ($j = $i + 1; $j < $pairCount; $j++) {
                $userAId = min($attendeeArray[$i], $attendeeArray[$j]);
                $userBId = max($attendeeArray[$i], $attendeeArray[$j]);

                $reactionScore = $this->computeReactionSync(
                    $reactionFingerprints->get($userAId, collect()),
                    $reactionFingerprints->get($userBId, collect()),
                );

                $qaScore = $qaEdges->get("{$userAId}:{$userBId}", 0.0);

                if ($reactionScore === 0.0 && $qaScore === 0.0) {
                    continue;
                }

                SessionEngagementEdge::updateOrCreate(
                    [
                        'event_session_id' => $session->id,
                        'user_a_id' => $userAId,
                        'user_b_id' => $userBId,
                    ],
                    [
                        'reaction_sync_score' => $reactionScore,
                        'qa_interaction_score' => $qaScore,
                    ]
                );
            }
        }
    }

    /**
     * Build per-user fingerprints: which 30-sec windows did each user react in?
     *
     * @return Collection<int, Collection<int, int>> userId => Collection of window indices
     */
    private function buildReactionFingerprints(EventSession $session, Collection $attendeeIds): Collection
    {
        $reactions = SessionReaction::where('event_session_id', $session->id)
            ->whereIn('user_id', $attendeeIds)
            ->orderBy('created_at')
            ->get(['user_id', 'created_at']);

        if ($reactions->isEmpty()) {
            return collect();
        }

        $sessionStart = $session->starts_at;

        return $reactions->groupBy('user_id')->map(function (Collection $userReactions) use ($sessionStart) {
            return $userReactions->map(function ($reaction) use ($sessionStart) {
                return (int) floor($sessionStart->diffInSeconds($reaction->created_at) / self::WINDOW_SECONDS);
            })->unique()->values();
        });
    }

    private function computeReactionSync(Collection $windowsA, Collection $windowsB): float
    {
        if ($windowsA->isEmpty() || $windowsB->isEmpty()) {
            return 0.0;
        }

        $shared = $windowsA->intersect($windowsB)->count();
        $total = max($windowsA->count(), $windowsB->count());

        return $total > 0 ? $shared / $total : 0.0;
    }

    /**
     * Build Q&A interaction edges: replies to each other, upvotes on each other's replies/questions.
     *
     * @return Collection<string, float> "userAId:userBId" => normalized score
     */
    private function buildQaInteractionEdges(EventSession $session, Collection $attendeeIds): Collection
    {
        $edges = collect();

        // Get all questions in this session
        $questions = SessionQuestion::where('event_session_id', $session->id)
            ->whereIn('user_id', $attendeeIds)
            ->with(['replies' => fn ($q) => $q->whereIn('user_id', $attendeeIds)])
            ->with(['votes' => fn ($q) => $q->whereIn('user_id', $attendeeIds)])
            ->get();

        foreach ($questions as $question) {
            // Replies to this question = interaction between question author and replier
            foreach ($question->replies as $reply) {
                if ($reply->user_id === $question->user_id) {
                    continue;
                }
                $key = $this->pairKey($question->user_id, $reply->user_id);
                $edges[$key] = ($edges[$key] ?? 0) + 0.4;
            }

            // Votes on the same question by different users
            $voterIds = $question->votes->pluck('user_id')->unique();
            $voterArray = $voterIds->toArray();
            for ($i = 0; $i < count($voterArray); $i++) {
                for ($j = $i + 1; $j < count($voterArray); $j++) {
                    $key = $this->pairKey($voterArray[$i], $voterArray[$j]);
                    $edges[$key] = ($edges[$key] ?? 0) + 0.3;
                }
            }
        }

        // Get reply votes
        $replies = SessionQuestionReply::whereIn('session_question_id', $questions->pluck('id'))
            ->whereIn('user_id', $attendeeIds)
            ->with(['votes' => fn ($q) => $q->whereIn('user_id', $attendeeIds)])
            ->get();

        foreach ($replies as $reply) {
            foreach ($reply->votes as $vote) {
                if ($vote->user_id === $reply->user_id) {
                    continue;
                }
                $key = $this->pairKey($reply->user_id, $vote->user_id);
                $edges[$key] = ($edges[$key] ?? 0) + 0.3;
            }
        }

        // Normalize: cap at 1.0
        return $edges->map(fn ($score) => min($score, 1.0));
    }

    private function pairKey(int $idA, int $idB): string
    {
        return min($idA, $idB) . ':' . max($idA, $idB);
    }
}
```

- [ ] **Step 6: Run migration and tests**

Run: `php artisan migrate --no-interaction && php artisan test --compact --filter="SessionEngagementEdgeTest"`
Expected: ALL PASS

- [ ] **Step 7: Lint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A && git commit -m "feat: add SessionEngagementService and engagement edges"
```

---

## Task 9: Create SessionEndedJob and SessionEnded event

**Files:**
- Create: `app/Jobs/SessionEndedJob.php`
- Create: `app/Events/SessionEnded.php`
- Modify: `app/Console/Commands/TriggerPostSessionMatching.php`

- [ ] **Step 1: Write the failing test**

Create a new section in `tests/Feature/SessionEngagementEdgeTest.php`:

```php
use App\Events\SessionEnded;
use App\Jobs\SessionEndedJob;
use Illuminate\Support\Facades\Event;

it('auto-checks-out remaining participants and computes engagement edges on session end', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->create([
        'starts_at' => now()->subHour(),
        'ends_at' => now()->subMinutes(1),
    ]);

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    // Both still checked in (no checked_out_at)
    SessionCheckIn::create(['user_id' => $userA->id, 'event_session_id' => $session->id]);
    SessionCheckIn::create(['user_id' => $userB->id, 'event_session_id' => $session->id]);

    // Both reacted in same window
    $baseTime = now()->subMinutes(30);
    SessionReaction::create(['user_id' => $userA->id, 'event_session_id' => $session->id, 'type' => 'fire', 'created_at' => $baseTime]);
    SessionReaction::create(['user_id' => $userB->id, 'event_session_id' => $session->id, 'type' => 'fire', 'created_at' => $baseTime->copy()->addSeconds(5)]);

    Event::fake([SessionEnded::class]);

    (new SessionEndedJob($session))->handle(
        app(SessionEngagementService::class),
        app(\App\Services\SuggestionService::class),
    );

    // Check-ins should be stamped
    expect(SessionCheckIn::where('event_session_id', $session->id)->whereNull('checked_out_at')->count())->toBe(0);

    // Engagement edge should exist
    expect(SessionEngagementEdge::where('event_session_id', $session->id)->count())->toBe(1);

    // SessionEnded event should be dispatched
    Event::assertDispatched(SessionEnded::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter="auto-checks-out remaining"`
Expected: FAIL — class not found

- [ ] **Step 3: Create SessionEnded event**

Run: `php artisan make:event SessionEnded --no-interaction`

```php
<?php

namespace App\Events;

use App\Models\EventSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionEnded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public EventSession $session,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("session.{$this->session->id}");
    }

    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->session->id,
            'ended_at' => $this->session->ends_at->toISOString(),
        ];
    }
}
```

- [ ] **Step 4: Create SessionEndedJob**

Run: `php artisan make:job SessionEndedJob --no-interaction`

```php
<?php

namespace App\Jobs;

use App\Events\SessionEnded;
use App\Models\EventSession;
use App\Models\SessionCheckIn;
use App\Services\SessionEngagementService;
use App\Services\SuggestionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SessionEndedJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public EventSession $session,
    ) {}

    public function handle(SessionEngagementService $engagementService, SuggestionService $suggestionService): void
    {
        // 1. Auto-checkout remaining participants
        SessionCheckIn::where('event_session_id', $this->session->id)
            ->whereNull('checked_out_at')
            ->update(['checked_out_at' => now()]);

        // 2. Compute engagement edges
        $engagementService->computeForSession($this->session);

        // 3. Generate session-affinity suggestions for all attendees
        $attendeeIds = SessionCheckIn::where('event_session_id', $this->session->id)
            ->pluck('user_id')
            ->unique();

        $event = $this->session->event;

        foreach ($attendeeIds as $userId) {
            $user = \App\Models\User::find($userId);
            if ($user) {
                $suggestionService->generateSessionAffinitySuggestions($user, $event, $this->session);
            }
        }

        // 4. Broadcast session ended
        SessionEnded::dispatch($this->session);
    }
}
```

- [ ] **Step 5: Update TriggerPostSessionMatching to dispatch SessionEndedJob**

In `app/Console/Commands/TriggerPostSessionMatching.php`, replace the `handle` method:

```php
public function handle(): int
{
    $sessions = EventSession::where('ends_at', '>=', now()->subMinutes(15))
        ->where('ends_at', '<=', now())
        ->whereDoesntHave('engagementEdges')
        ->get();

    $count = 0;
    foreach ($sessions as $session) {
        SessionEndedJob::dispatch($session);
        $count++;
    }

    $this->info("Dispatched SessionEndedJob for {$count} sessions.");

    return self::SUCCESS;
}
```

Add imports:

```php
use App\Jobs\SessionEndedJob;
use App\Models\EventSession;
```

Add `engagementEdges` relation to `EventSession`:

```php
public function engagementEdges(): HasMany
{
    return $this->hasMany(SessionEngagementEdge::class);
}
```

- [ ] **Step 6: Run tests**

Run: `php artisan test --compact --filter="auto-checks-out remaining"`
Expected: PASS (will fail on `generateSessionAffinitySuggestions` — that's Task 10)

Note: This test may fail because `generateSessionAffinitySuggestions` doesn't exist yet. If so, this is expected and will be fixed in Task 10. For now, temporarily mock the method or skip that assertion and come back.

- [ ] **Step 7: Lint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A && git commit -m "feat: add SessionEndedJob with auto-checkout and engagement computation"
```

---

## Task 10: Add session_affinity trigger to SuggestionService and update MatchingService

**Files:**
- Modify: `app/Services/SuggestionService.php`
- Modify: `app/Services/MatchingService.php`
- Test: `tests/Feature/SessionAffinityMatchingTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/SessionAffinityMatchingTest.php`:

```php
<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionCheckIn;
use App\Models\SessionEngagementEdge;
use App\Models\Suggestion;
use App\Models\User;
use App\Services\MatchingService;
use App\Services\SuggestionService;

it('includes session affinity in matching score', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->create([
        'starts_at' => now()->subHour(),
        'ends_at' => now()->subMinutes(5),
    ]);

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $event->participants()->attach($userA, ['participant_type' => 'physical', 'status' => 'available']);
    $event->participants()->attach($userB, ['participant_type' => 'remote', 'status' => 'available']);

    SessionCheckIn::create(['user_id' => $userA->id, 'event_session_id' => $session->id, 'checked_out_at' => now()->subMinutes(5)]);
    SessionCheckIn::create(['user_id' => $userB->id, 'event_session_id' => $session->id, 'checked_out_at' => now()->subMinutes(5)]);

    // Pre-computed engagement edge with high affinity
    SessionEngagementEdge::create([
        'event_session_id' => $session->id,
        'user_a_id' => min($userA->id, $userB->id),
        'user_b_id' => max($userA->id, $userB->id),
        'reaction_sync_score' => 0.8,
        'qa_interaction_score' => 0.6,
    ]);

    $matchingService = app(MatchingService::class);
    $score = $matchingService->score($userA, $userB, $event);

    // Score should be higher than without session affinity
    expect($score)->toBeGreaterThan(0.0);
});

it('generates session affinity suggestions after session ends', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->create([
        'starts_at' => now()->subHour(),
        'ends_at' => now()->subMinutes(5),
    ]);

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $event->participants()->attach($userA, ['participant_type' => 'physical', 'status' => 'available']);
    $event->participants()->attach($userB, ['participant_type' => 'remote', 'status' => 'available']);

    SessionCheckIn::create(['user_id' => $userA->id, 'event_session_id' => $session->id, 'checked_out_at' => now()->subMinutes(5)]);
    SessionCheckIn::create(['user_id' => $userB->id, 'event_session_id' => $session->id, 'checked_out_at' => now()->subMinutes(5)]);

    SessionEngagementEdge::create([
        'event_session_id' => $session->id,
        'user_a_id' => min($userA->id, $userB->id),
        'user_b_id' => max($userA->id, $userB->id),
        'reaction_sync_score' => 0.9,
        'qa_interaction_score' => 0.5,
    ]);

    $suggestionService = app(SuggestionService::class);
    $suggestions = $suggestionService->generateSessionAffinitySuggestions($userA, $event, $session);

    expect($suggestions)->not->toBeEmpty();
    expect($suggestions->first()->trigger)->toBe('session_affinity');
    expect($suggestions->first()->reason)->toContain('session');
});

it('enforces cross-world reranking in top matches', function () {
    $event = Event::factory()->create();

    $physicalA = User::factory()->create();
    $physicalB = User::factory()->create();
    $physicalC = User::factory()->create();
    $remoteD = User::factory()->create();

    $event->participants()->attach($physicalA, ['participant_type' => 'physical', 'status' => 'available']);
    $event->participants()->attach($physicalB, ['participant_type' => 'physical', 'status' => 'available']);
    $event->participants()->attach($physicalC, ['participant_type' => 'physical', 'status' => 'available']);
    $event->participants()->attach($remoteD, ['participant_type' => 'remote', 'status' => 'available']);

    $matchingService = app(MatchingService::class);
    $matches = $matchingService->topMatches($physicalA, $event, 3);

    // At least one match should be cross-world (remote)
    $crossWorld = $matches->filter(fn ($m) => $event->participants()
        ->where('user_id', $m['user']->id)
        ->first()?->pivot->participant_type === 'remote'
    );

    expect($crossWorld)->not->toBeEmpty();
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter="SessionAffinityMatchingTest"`
Expected: FAIL — method doesn't exist

- [ ] **Step 3: Update MatchingService**

In `app/Services/MatchingService.php`, update the weights and add session affinity:

```php
private float $w1 = 0.3;   // interest overlap
private float $w2 = 0.25;  // context match
private float $w3 = 0.25;  // session affinity
```

Update the `score` method:

```php
public function score(User $userA, User $userB, Event $event): float
{
    $interestOverlap = $this->interestOverlap($userA, $userB, $event);
    $contextMatch = $this->contextMatch($userA, $userB, $event);
    $sessionAffinity = $this->sessionAffinity($userA, $userB, $event);
    $availability = $this->availability($userA, $userB, $event);

    $relevance = ($this->w1 * $interestOverlap) + ($this->w2 * $contextMatch) + ($this->w3 * $sessionAffinity);

    return $relevance * max($availability, 0.05);
}
```

Add the `sessionAffinity` method:

```php
public function sessionAffinity(User $userA, User $userB, Event $event): float
{
    $userAId = min($userA->id, $userB->id);
    $userBId = max($userA->id, $userB->id);

    $edge = SessionEngagementEdge::where('user_a_id', $userAId)
        ->where('user_b_id', $userBId)
        ->whereHas('eventSession', fn ($q) => $q->where('event_id', $event->id))
        ->orderByDesc('created_at')
        ->first();

    if (! $edge) {
        return 0.0;
    }

    return $edge->score();
}
```

Add import: `use App\Models\SessionEngagementEdge;`

Update `topMatches` to add cross-world reranking after sorting:

```php
public function topMatches(User $user, Event $event, int $limit = 3): Collection
{
    // ... existing filtering and scoring logic ...

    $sorted = $scored->sortByDesc('score')->values();

    // Cross-world reranking: ensure at least 1 of top $limit is cross-world
    return $this->rerankForCrossWorld($sorted, $user, $event, $limit);
}

private function rerankForCrossWorld(Collection $sorted, User $user, Event $event, int $limit): Collection
{
    $top = $sorted->take($limit);
    $rest = $sorted->skip($limit);

    $userType = $event->participants()->where('user_id', $user->id)->first()?->pivot->participant_type;

    if (! $userType) {
        return $top;
    }

    $hasCrossWorld = $top->contains(function ($match) use ($event, $userType) {
        $matchType = $event->participants()->where('user_id', $match['user']->id)->first()?->pivot->participant_type;
        return $matchType && $matchType !== $userType;
    });

    if ($hasCrossWorld || $top->count() < $limit) {
        return $top;
    }

    // Find highest-scored cross-world candidate from the rest
    $crossWorldCandidate = $rest->first(function ($match) use ($event, $userType) {
        $matchType = $event->participants()->where('user_id', $match['user']->id)->first()?->pivot->participant_type;
        return $matchType && $matchType !== $userType;
    });

    if ($crossWorldCandidate) {
        $top = $top->take($limit - 1)->push($crossWorldCandidate);
    }

    return $top;
}
```

- [ ] **Step 4: Add generateSessionAffinitySuggestions to SuggestionService**

In `app/Services/SuggestionService.php`, add:

```php
public function generateSessionAffinitySuggestions(User $user, Event $event, EventSession $session): Collection
{
    $activeSuggestions = Suggestion::where('suggested_to_id', $user->id)
        ->where('event_id', $event->id)
        ->active()
        ->count();

    $slotsAvailable = self::MAX_ACTIVE - $activeSuggestions;

    if ($slotsAvailable <= 0) {
        return collect();
    }

    $connectedIds = $this->getConnectedUserIds($user, $event);

    // Get engagement edges for this user in this session
    $edges = SessionEngagementEdge::where('event_session_id', $session->id)
        ->where(fn ($q) => $q->where('user_a_id', $user->id)->orWhere('user_b_id', $user->id))
        ->orderByRaw('(reaction_sync_score * 0.6 + qa_interaction_score * 0.4) DESC')
        ->limit($slotsAvailable + 5)
        ->get();

    $suggestions = collect();

    foreach ($edges as $edge) {
        if ($suggestions->count() >= $slotsAvailable) {
            break;
        }

        $candidateId = $edge->user_a_id === $user->id ? $edge->user_b_id : $edge->user_a_id;

        if ($connectedIds->contains($candidateId)) {
            continue;
        }

        $candidate = User::find($candidateId);
        if (! $candidate) {
            continue;
        }

        $reason = $this->buildSessionAffinityReason($edge, $session);

        $suggestion = Suggestion::create([
            'suggested_to_id' => $user->id,
            'suggested_user_id' => $candidateId,
            'event_id' => $event->id,
            'score' => $edge->score(),
            'reason' => $reason,
            'trigger' => 'session_affinity',
            'status' => 'pending',
            'expires_at' => now()->addMinutes(15),
        ]);

        $suggestions->push($suggestion);
    }

    return $suggestions;
}

private function buildSessionAffinityReason(SessionEngagementEdge $edge, EventSession $session): string
{
    $parts = [];

    if ($edge->reaction_sync_score > 0.3) {
        $parts[] = "You both vibed during \"{$session->title}\"";
    }

    if ($edge->qa_interaction_score > 0.2) {
        $parts[] = 'You discussed the same topics';
    }

    if (empty($parts)) {
        $parts[] = "You were both engaged in \"{$session->title}\"";
    }

    return implode(' · ', $parts);
}
```

Add imports:

```php
use App\Models\EventSession;
use App\Models\SessionEngagementEdge;
```

- [ ] **Step 5: Run tests**

Run: `php artisan test --compact --filter="SessionAffinityMatchingTest"`
Expected: ALL PASS

- [ ] **Step 6: Run full test suite to check for regressions**

Run: `php artisan test --compact`
Expected: ALL PASS (matching formula change may affect existing tests — fix weight expectations if needed)

- [ ] **Step 7: Lint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A && git commit -m "feat: add session affinity to matching engine and suggestion service"
```

---

## Task 11: Update SessionController.show() props for threaded Q&A

**Files:**
- Modify: `app/Http/Controllers/SessionController.php`

- [ ] **Step 1: Write the failing test**

Add to existing session controller tests or create if needed:

```php
it('returns questions with replies and moderation data in session show', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $session = EventSession::factory()->for($event)->live()->create(['speaker_user_id' => $organizer->id]);
    $user = User::factory()->create();
    $event->participants()->attach($user, ['participant_type' => 'physical', 'status' => 'in_session']);
    SessionCheckIn::create(['user_id' => $user->id, 'event_session_id' => $session->id]);

    $question = SessionQuestion::factory()->create(['event_session_id' => $session->id, 'is_pinned' => true]);
    $reply = SessionQuestionReply::create([
        'session_question_id' => $question->id,
        'user_id' => $organizer->id,
        'body' => 'Great question!',
    ]);

    $response = $this->actingAs($user)->get(route('event.sessions.show', [$event, $session]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Event/SessionDetail')
        ->has('questions.0.replies', 1)
        ->where('questions.0.is_pinned', true)
        ->where('questions.0.is_hidden', false)
        ->has('questions.0.replies.0.user')
    );
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter="returns questions with replies"`
Expected: FAIL — replies not included in props

- [ ] **Step 3: Update SessionController.show()**

In `app/Http/Controllers/SessionController.php`, update the `show` method's questions query to include replies, moderation fields, and filter hidden questions for non-organizers:

```php
$isOrganizer = $event->organizer_id === $request->user()->id;
$isSpeaker = $session->speaker_user_id === $request->user()->id;

$questionsQuery = $session->questions()
    ->with(['user:id,name', 'replies' => fn ($q) => $q->with('user:id,name')->withCount('votes'), 'votes'])
    ->withCount('votes');

if (! $isOrganizer && ! $isSpeaker) {
    $questionsQuery->where('is_hidden', false);
}

$questions = $questionsQuery
    ->orderByDesc('is_pinned')
    ->orderByDesc('votes_count')
    ->get()
    ->map(fn ($q) => [
        'id' => $q->id,
        'body' => $q->body,
        'user' => ['id' => $q->user->id, 'name' => $q->user->name],
        'votes_count' => $q->votes_count,
        'viewer_has_voted' => $q->votes->contains('user_id', $request->user()->id),
        'is_answered' => $q->is_answered,
        'is_pinned' => $q->is_pinned,
        'is_hidden' => $q->is_hidden,
        'answered_by' => $q->answered_by,
        'replies' => $q->replies->map(fn ($r) => [
            'id' => $r->id,
            'body' => $r->body,
            'user' => ['id' => $r->user->id, 'name' => $r->user->name],
            'votes_count' => $r->votes_count,
            'viewer_has_voted' => $r->votes->contains('user_id', $request->user()->id),
            'is_speaker' => $session->speaker_user_id === $r->user_id,
            'is_organizer' => $event->organizer_id === $r->user_id,
            'created_at' => $r->created_at->toISOString(),
        ]),
    ]);
```

Also update the `viewer` prop to include speaker status:

```php
'viewer' => [
    'is_organizer' => $isOrganizer,
    'is_speaker' => $isSpeaker,
    'is_moderator' => $isOrganizer || $isSpeaker,
    'participant_type' => $pivot?->participant_type,
    'is_checked_in' => $session->hasActiveCheckInFor($request->user()),
    'can_join' => $session->isJoinable(),
    'can_interact' => $session->canInteract(),
],
```

And add `speaker_user_id` to the session prop:

```php
'session' => [
    // ... existing fields ...
    'speaker_user_id' => $session->speaker_user_id,
],
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --compact --filter="returns questions with replies"`
Expected: PASS

- [ ] **Step 5: Lint and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A && git commit -m "feat: include replies and moderation data in session show props"
```

---

## Task 12: Add SessionModerate Inertia page (organizer view)

**Files:**
- Modify: `app/Http/Controllers/SessionController.php` (add moderate method)
- Create: `resources/js/pages/Event/SessionModerate.vue`
- Modify: `routes/web.php`

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/SessionModerateTest.php`:

```php
it('renders the session moderate page for organizers', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);
    $session = EventSession::factory()->for($event)->live()->create();

    $response = $this->actingAs($organizer)->get(
        route('event.sessions.moderate', [$event, $session])
    );

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Event/SessionModerate'));
});

it('rejects non-organizer from moderate page', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->live()->create();

    $response = $this->actingAs($user)->get(
        route('event.sessions.moderate', [$event, $session])
    );

    $response->assertForbidden();
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter="renders the session moderate page"`
Expected: FAIL — route not defined

- [ ] **Step 3: Add route**

In `routes/web.php`:

```php
Route::get('/event/{event:slug}/sessions/{session}/moderate', [SessionController::class, 'moderate'])
    ->name('event.sessions.moderate')->scopeBindings();
```

- [ ] **Step 4: Add moderate method to SessionController**

In `app/Http/Controllers/SessionController.php`:

```php
public function moderate(Request $request, Event $event, EventSession $session): Response
{
    abort_unless($event->organizer_id === $request->user()->id, 403);

    $questions = $session->questions()
        ->with(['user:id,name', 'replies' => fn ($q) => $q->with('user:id,name')->withCount('votes'), 'votes'])
        ->withCount('votes')
        ->orderByDesc('is_pinned')
        ->orderByDesc('votes_count')
        ->get()
        ->map(fn ($q) => [
            'id' => $q->id,
            'body' => $q->body,
            'user' => ['id' => $q->user->id, 'name' => $q->user->name],
            'votes_count' => $q->votes_count,
            'is_answered' => $q->is_answered,
            'is_pinned' => $q->is_pinned,
            'is_hidden' => $q->is_hidden,
            'answered_by' => $q->answered_by,
            'replies' => $q->replies->map(fn ($r) => [
                'id' => $r->id,
                'body' => $r->body,
                'user' => ['id' => $r->user->id, 'name' => $r->user->name],
                'votes_count' => $r->votes_count,
                'is_speaker' => $session->speaker_user_id === $r->user_id,
                'is_organizer' => $event->organizer_id === $r->user_id,
                'created_at' => $r->created_at->toISOString(),
            ]),
        ]);

    $reactions = $session->reactions()
        ->selectRaw("FLOOR(TIMESTAMPDIFF(SECOND, ?, created_at) / 30) as time_window, type, COUNT(*) as count", [$session->starts_at])
        ->groupBy('time_window', 'type')
        ->orderBy('time_window')
        ->get();

    $participants = $session->checkIns()
        ->with('user:id,name')
        ->get()
        ->map(fn ($ci) => [
            'id' => $ci->user->id,
            'name' => $ci->user->name,
            'participant_type' => $event->participants()->where('user_id', $ci->user_id)->first()?->pivot->participant_type,
            'is_active' => $ci->checked_out_at === null,
        ]);

    $physicalCount = $participants->where('participant_type', 'physical')->count();
    $remoteCount = $participants->where('participant_type', 'remote')->count();

    return Inertia::render('Event/SessionModerate', [
        'event' => ['id' => $event->id, 'name' => $event->name, 'slug' => $event->slug],
        'session' => [
            'id' => $session->id,
            'title' => $session->title,
            'speaker' => $session->speaker,
            'starts_at' => $session->starts_at->toISOString(),
            'ends_at' => $session->ends_at->toISOString(),
            'is_live' => $session->isLive(),
        ],
        'questions' => $questions,
        'reactions' => $reactions,
        'stats' => [
            'total_reactions' => $session->reactions()->count(),
            'total_questions' => $session->questions()->count(),
            'physical_count' => $physicalCount,
            'remote_count' => $remoteCount,
            'total_participants' => $participants->count(),
        ],
    ]);
}
```

- [ ] **Step 5: Create SessionModerate.vue**

Create `resources/js/pages/Event/SessionModerate.vue`. This is a three-panel layout: reaction heatmap (left), Q&A feed (center), engagement summary (right). The full Vue implementation should reference BoothDetail.vue for the thread/moderation pattern and use the Paper design system for styling. The component receives props: `event`, `session`, `questions`, `reactions`, `stats`.

Key interactions:
- Pin/unpin: POST to `event.sessions.questions.pin`
- Hide/show: POST to `event.sessions.questions.hide`
- Mark answered: POST to `event.sessions.questions.answer`
- Reply: POST to `event.sessions.questions.replies.store`
- Echo listener on `session.{sessionId}` for real-time updates

*(Full Vue template deferred to implementation — the agent should reference BoothDetail.vue pattern and Paper designs for exact layout.)*

- [ ] **Step 6: Run tests and build**

Run: `php artisan test --compact --filter="SessionModerateTest" && npm run build`
Expected: PASS

- [ ] **Step 7: Generate Wayfinder routes, lint, and commit**

```bash
php artisan wayfinder:generate
vendor/bin/pint --dirty --format agent
git add -A && git commit -m "feat: add organizer session moderation page"
```

---

## Task 13: Create PostSessionConnections page

**Files:**
- Create: `resources/js/pages/Event/PostSessionConnections.vue`
- Modify: `app/Http/Controllers/SessionController.php` (add postSession method)
- Modify: `routes/web.php`
- Test: `tests/Feature/PostSessionConnectionTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/PostSessionConnectionTest.php`:

```php
<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionCheckIn;
use App\Models\Suggestion;
use App\Models\User;

it('renders post-session connections page with session affinity suggestions', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->create([
        'starts_at' => now()->subHour(),
        'ends_at' => now()->subMinutes(5),
    ]);

    $user = User::factory()->create();
    $event->participants()->attach($user, ['participant_type' => 'physical', 'status' => 'available']);
    SessionCheckIn::create(['user_id' => $user->id, 'event_session_id' => $session->id, 'checked_out_at' => now()->subMinutes(5)]);

    $suggestedUser = User::factory()->create();
    Suggestion::create([
        'suggested_to_id' => $user->id,
        'suggested_user_id' => $suggestedUser->id,
        'event_id' => $event->id,
        'score' => 0.85,
        'reason' => 'You both vibed during "Keynote"',
        'trigger' => 'session_affinity',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->actingAs($user)->get(
        route('event.sessions.post-session', [$event, $session])
    );

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Event/PostSessionConnections')
        ->has('suggestions', 1)
        ->where('suggestions.0.reason', 'You both vibed during "Keynote"')
    );
});

it('returns 404 for session that ended more than 15 minutes ago', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->for($event)->create([
        'starts_at' => now()->subHours(2),
        'ends_at' => now()->subMinutes(30),
    ]);

    $user = User::factory()->create();
    $event->participants()->attach($user, ['participant_type' => 'physical', 'status' => 'available']);
    SessionCheckIn::create(['user_id' => $user->id, 'event_session_id' => $session->id, 'checked_out_at' => now()->subMinutes(30)]);

    $response = $this->actingAs($user)->get(
        route('event.sessions.post-session', [$event, $session])
    );

    $response->assertNotFound();
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter="PostSessionConnectionTest"`
Expected: FAIL — route not defined

- [ ] **Step 3: Add route**

In `routes/web.php`:

```php
Route::get('/event/{event:slug}/sessions/{session}/connections', [SessionController::class, 'postSession'])
    ->name('event.sessions.post-session')->scopeBindings();
```

- [ ] **Step 4: Add postSession method to SessionController**

In `app/Http/Controllers/SessionController.php`:

```php
public function postSession(Request $request, Event $event, EventSession $session): Response
{
    // Only available within 15 minutes after session ends
    abort_unless(
        $session->ends_at?->isPast() && $session->ends_at->diffInMinutes(now()) <= 15,
        404
    );

    // Must have attended
    $attended = SessionCheckIn::where('user_id', $request->user()->id)
        ->where('event_session_id', $session->id)
        ->exists();
    abort_unless($attended, 404);

    $suggestions = Suggestion::where('suggested_to_id', $request->user()->id)
        ->where('event_id', $event->id)
        ->where('trigger', 'session_affinity')
        ->active()
        ->with('suggestedUser:id,name')
        ->orderByDesc('score')
        ->get()
        ->map(fn ($s) => [
            'id' => $s->id,
            'user' => [
                'id' => $s->suggestedUser->id,
                'name' => $s->suggestedUser->name,
                'participant_type' => $event->participants()->where('user_id', $s->suggested_user_id)->first()?->pivot->participant_type,
            ],
            'score' => $s->score,
            'reason' => $s->reason,
        ]);

    return Inertia::render('Event/PostSessionConnections', [
        'event' => ['id' => $event->id, 'name' => $event->name, 'slug' => $event->slug],
        'session' => ['id' => $session->id, 'title' => $session->title],
        'suggestions' => $suggestions,
    ]);
}
```

Add import: `use App\Models\Suggestion;`

- [ ] **Step 5: Create PostSessionConnections.vue**

Create `resources/js/pages/Event/PostSessionConnections.vue`. Shows "People you vibed with" with suggestion cards. Each card shows name, participant type badge, reason, and a ping button. The ping button uses the existing ping route. Reference Paper designs for layout.

*(Full Vue template deferred to implementation — the agent should reference existing suggestion UI patterns.)*

- [ ] **Step 6: Run tests and build**

Run: `php artisan test --compact --filter="PostSessionConnectionTest" && npm run build`
Expected: PASS

- [ ] **Step 7: Generate Wayfinder routes, lint, and commit**

```bash
php artisan wayfinder:generate
vendor/bin/pint --dirty --format agent
git add -A && git commit -m "feat: add post-session connections page"
```

---

## Task 14: Update SessionDetail.vue with threaded Q&A and reaction cluster badge

**Files:**
- Modify: `resources/js/pages/Event/SessionDetail.vue`

- [ ] **Step 1: Update the Vue component**

This task modifies the existing SessionDetail.vue to add:

1. **Reaction cluster badge** — client-side computed. Track incoming reactions in 30-sec windows. When the current user reacts and others reacted in the same window, show a pill: "N others felt that too". Auto-dismiss after 15 seconds.

2. **Threaded Q&A** — each question shows its replies underneath. Reply form appears when tapping a question. Reply votes with upvote button. Speaker/organizer badges on replies. Pinned questions show at top with visual indicator. Answered questions show checkmark and sort lower.

3. **Moderator actions** — if `viewer.is_moderator`, show pin/hide/answer buttons on each question. Show "Moderate" link to full moderation page.

4. **Echo listeners** — listen for `SessionQuestionReplyPosted`, `SessionQuestionPinned`, `SessionEnded` events on `session.{sessionId}` channel. On `SessionEnded`, redirect to post-session connections page.

Key changes:
- Add Echo channel subscription in `onMounted`, cleanup in `onUnmounted`
- Track reaction windows in a reactive `Map<number, Set<number>>` (window index → user IDs)
- Show cluster badge as a fixed-position pill at bottom of screen
- Expand question cards to include replies list and reply form
- Add moderator action buttons conditionally

*(Full Vue diff deferred to implementation — the agent should read current SessionDetail.vue and BoothDetail.vue for patterns.)*

- [ ] **Step 2: Build and verify**

Run: `npm run build`
Expected: Build succeeds

- [ ] **Step 3: Generate Wayfinder routes, lint, and commit**

```bash
php artisan wayfinder:generate
vendor/bin/pint --dirty --format agent
git add -A && git commit -m "feat: update SessionDetail with threaded Q&A and reaction clusters"
```

---

## Task 15: Final integration test and cleanup

**Files:**
- Run full test suite
- Verify all routes registered
- Clean up any remaining issues

- [ ] **Step 1: Run full test suite**

Run: `php artisan test --compact`
Expected: ALL PASS

- [ ] **Step 2: Verify routes**

Run: `php artisan route:list --name=event.sessions`
Expected: See all new routes (replies, vote, pin, hide, answer, moderate, post-session)

- [ ] **Step 3: Run Pint on all modified files**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 4: Final commit if any changes**

```bash
git add -A && git commit -m "chore: final cleanup for session connections feature"
```