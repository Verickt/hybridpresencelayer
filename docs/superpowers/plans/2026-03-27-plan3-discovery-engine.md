# Plan 3: Discovery Engine — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the matching algorithm, smart suggestions, context-triggered suggestions, serendipity mode, and search. The engine that decides "who should meet whom — right now."

**Architecture:** A `MatchingService` computes relevance scores between participant pairs using interest overlap and context match, multiplied by availability (so busy users are effectively filtered). Scoring uses a preloaded participant snapshot to avoid N+1 queries. A `SuggestionService` manages suggestion lifecycle (create, expire, decline, accept) with DB transactions to prevent race conditions. A scheduled command expires stale suggestions. Search covers name, company, and interest tags.

**Tech Stack:** Laravel 13, Pest v4, Inertia v3, Vue 3

**Depends on:** Plan 1 (data model), Plan 2 (presence/status)

## TDD Standard

- Start each task with failing tests before implementation.
- Cover negative and edge cases: empty result sets, expired suggestions, duplicate suggestions, blocked/invisible users, already-connected users, and stale suggestion acceptance.
- For Inertia suggestion/search endpoints, add `assertInertia` coverage for prop shape, caps/limits, and privacy omissions.
- For suggestion/search pages, add browser smoke coverage and at least one browser flow for success plus one decline/empty/failure path.

---

## File Structure

### Migrations
```
create_suggestions_table.php
```

### Models
```
app/Models/Suggestion.php
```

### Services
```
app/Services/MatchingService.php — scoring algorithm
app/Services/SuggestionService.php — suggestion lifecycle
```

### Controllers
```
app/Http/Controllers/SuggestionController.php
app/Http/Controllers/SearchController.php
```

### Commands
```
app/Console/Commands/ExpireSuggestions.php
```

### Vue Components
```
resources/js/components/discovery/SuggestionCard.vue
resources/js/components/discovery/SearchBar.vue
```

---

## Task 1: Suggestion Model

**Files:**
- Create: `database/migrations/xxxx_create_suggestions_table.php`
- Create: `app/Models/Suggestion.php`
- Create: `database/factories/SuggestionFactory.php`
- Create: `tests/Feature/Models/SuggestionTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Models/SuggestionTest.php
<?php

use App\Models\Event;
use App\Models\Suggestion;
use App\Models\User;

it('creates a suggestion between two users', function () {
    $suggestion = Suggestion::factory()->create();

    expect($suggestion->suggestedTo)->toBeInstanceOf(User::class)
        ->and($suggestion->suggestedUser)->toBeInstanceOf(User::class)
        ->and($suggestion->event)->toBeInstanceOf(Event::class)
        ->and($suggestion->score)->toBeFloat();
});

it('tracks suggestion status', function () {
    $suggestion = Suggestion::factory()->create(['status' => 'pending']);

    expect($suggestion->status)->toBe('pending');
});

it('expires after 15 minutes', function () {
    $fresh = Suggestion::factory()->create(['expires_at' => now()->addMinutes(15)]);
    $expired = Suggestion::factory()->create(['expires_at' => now()->subMinute()]);

    expect($fresh->isExpired())->toBeFalse()
        ->and($expired->isExpired())->toBeTrue();
});

it('scopes to active suggestions', function () {
    Suggestion::factory()->create(['status' => 'pending', 'expires_at' => now()->addMinutes(10)]);
    Suggestion::factory()->create(['status' => 'declined', 'expires_at' => now()->addMinutes(10)]);
    Suggestion::factory()->create(['status' => 'pending', 'expires_at' => now()->subMinute()]);

    expect(Suggestion::active()->count())->toBe(1);
});

it('scopes to a specific user', function () {
    $user = User::factory()->create();
    Suggestion::factory()->create(['suggested_to_id' => $user->id]);
    Suggestion::factory()->create();

    expect(Suggestion::forUser($user)->count())->toBe(1);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=SuggestionTest`
Expected: FAIL

- [ ] **Step 3: Create migration**

Run: `php artisan make:migration create_suggestions_table --no-interaction`

```php
Schema::create('suggestions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('suggested_to_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('suggested_user_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('event_id')->constrained()->cascadeOnDelete();
    $table->float('score');
    $table->string('reason')->nullable();
    $table->enum('status', ['pending', 'accepted', 'declined', 'expired'])->default('pending');
    $table->string('trigger')->nullable();
    $table->timestamp('expires_at');
    $table->timestamps();

    $table->index(['suggested_to_id', 'status']);
    // No unique constraint on status — same pair can have multiple historical declined/expired rows
    // Only enforce uniqueness for active pending suggestions in application logic
    $table->index(['suggested_to_id', 'suggested_user_id', 'event_id']);
});
```

- [ ] **Step 4: Create model**

```php
// app/Models/Suggestion.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Suggestion extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'score' => 'float',
            'expires_at' => 'datetime',
        ];
    }

    public function suggestedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suggested_to_id');
    }

    public function suggestedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suggested_user_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '>', now());
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('suggested_to_id', $user->id);
    }
}
```

- [ ] **Step 5: Create factory**

```php
// database/factories/SuggestionFactory.php
<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SuggestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'suggested_to_id' => User::factory(),
            'suggested_user_id' => User::factory(),
            'event_id' => Event::factory(),
            'score' => $this->faker->randomFloat(2, 0, 1),
            'reason' => 'Shares 2 interest tags',
            'status' => 'pending',
            'trigger' => 'interest_overlap',
            'expires_at' => now()->addMinutes(15),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subMinute(),
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn () => [
            'status' => 'declined',
        ]);
    }
}
```

- [ ] **Step 6: Run migration and tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=SuggestionTest`
Expected: All 5 tests PASS

- [ ] **Step 7: Commit**

```bash
git add database/migrations/*suggestions* app/Models/Suggestion.php database/factories/SuggestionFactory.php tests/Feature/Models/SuggestionTest.php
git commit -m "feat: add Suggestion model with expiry, scopes, and status tracking"
```

---

## Task 2: Matching Service

**Files:**
- Create: `app/Services/MatchingService.php`
- Create: `tests/Feature/Services/MatchingServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Services/MatchingServiceTest.php
<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\InterestTag;
use App\Models\User;
use App\Services\MatchingService;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->service = app(MatchingService::class);
});

it('scores higher when users share more tags', function () {
    $tagA = InterestTag::factory()->create(['name' => 'Zero Trust']);
    $tagB = InterestTag::factory()->create(['name' => 'DevOps']);
    $tagC = InterestTag::factory()->create(['name' => 'AI/ML']);

    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $userC = User::factory()->create();

    // A has tags: Zero Trust, DevOps
    $userA->interestTags()->attach([$tagA->id, $tagB->id], ['event_id' => $this->event->id]);
    // B has tags: Zero Trust, DevOps (2 shared with A)
    $userB->interestTags()->attach([$tagA->id, $tagB->id], ['event_id' => $this->event->id]);
    // C has tags: AI/ML (0 shared with A)
    $userC->interestTags()->attach([$tagC->id], ['event_id' => $this->event->id]);

    $this->event->participants()->attach($userA, ['participant_type' => 'physical', 'status' => 'available']);
    $this->event->participants()->attach($userB, ['participant_type' => 'remote', 'status' => 'available']);
    $this->event->participants()->attach($userC, ['participant_type' => 'remote', 'status' => 'available']);

    $scoreAB = $this->service->score($userA, $userB, $this->event);
    $scoreAC = $this->service->score($userA, $userC, $this->event);

    expect($scoreAB)->toBeGreaterThan($scoreAC);
});

it('scores higher when both are in the same session', function () {
    $tag = InterestTag::factory()->create();
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $userA->interestTags()->attach($tag, ['event_id' => $this->event->id]);
    $userB->interestTags()->attach($tag, ['event_id' => $this->event->id]);

    $session = EventSession::factory()->live()->create(['event_id' => $this->event->id]);

    $this->event->participants()->attach($userA, ['participant_type' => 'physical', 'status' => 'in_session', 'context_badge' => "Watching: {$session->title}"]);
    $this->event->participants()->attach($userB, ['participant_type' => 'remote', 'status' => 'in_session', 'context_badge' => "Watching: {$session->title}"]);

    \App\Models\SessionCheckIn::create(['user_id' => $userA->id, 'event_session_id' => $session->id]);
    \App\Models\SessionCheckIn::create(['user_id' => $userB->id, 'event_session_id' => $session->id]);

    $withSession = $this->service->score($userA, $userB, $this->event);

    // Remove check-ins and re-score
    \App\Models\SessionCheckIn::truncate();
    $withoutSession = $this->service->score($userA, $userB, $this->event);

    expect($withSession)->toBeGreaterThan($withoutSession);
});

it('scores lower when a user is busy', function () {
    $tag = InterestTag::factory()->create();
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $userA->interestTags()->attach($tag, ['event_id' => $this->event->id]);
    $userB->interestTags()->attach($tag, ['event_id' => $this->event->id]);

    $this->event->participants()->attach($userA, ['participant_type' => 'physical', 'status' => 'available']);
    $this->event->participants()->attach($userB, ['participant_type' => 'remote', 'status' => 'available']);

    $availableScore = $this->service->score($userA, $userB, $this->event);

    $userB->events()->updateExistingPivot($this->event->id, ['status' => 'busy']);
    $busyScore = $this->service->score($userA, $userB, $this->event);

    expect($availableScore)->toBeGreaterThan($busyScore);
});

it('returns top matches for a user', function () {
    $tag = InterestTag::factory()->create();
    $userA = User::factory()->create();
    $userA->interestTags()->attach($tag, ['event_id' => $this->event->id]);
    $this->event->participants()->attach($userA, ['participant_type' => 'physical', 'status' => 'available']);

    $others = User::factory(5)->create();
    foreach ($others as $other) {
        $other->interestTags()->attach($tag, ['event_id' => $this->event->id]);
        $this->event->participants()->attach($other, ['participant_type' => 'remote', 'status' => 'available']);
    }

    $matches = $this->service->topMatches($userA, $this->event, limit: 3);

    expect($matches)->toHaveCount(3);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=MatchingServiceTest`
Expected: FAIL

- [ ] **Step 3: Create MatchingService**

```php
// app/Services/MatchingService.php
<?php

namespace App\Services;

use App\Models\Event;
use App\Models\SessionCheckIn;
use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Support\Collection;

class MatchingService
{
    private float $w1 = 0.4;  // interest overlap
    private float $w2 = 0.35; // context match
    private float $w3 = 0.25; // availability

    private const STATUS_SCORES = [
        'available' => 1.0,
        'at_booth' => 0.5,
        'in_session' => 0.3,
        'away' => 0.1,
        'busy' => 0.0,
    ];

    public function score(User $userA, User $userB, Event $event): float
    {
        $interestOverlap = $this->interestOverlap($userA, $userB, $event);
        $contextMatch = $this->contextMatch($userA, $userB, $event);
        $availability = $this->availability($userA, $userB, $event);

        // Availability multiplies relevance — busy users (0.0) are effectively filtered out
        $relevance = ($this->w1 * $interestOverlap) + ($this->w2 * $contextMatch);
        return $relevance * max($availability, 0.05);
    }

    public function topMatches(User $user, Event $event, int $limit = 3): Collection
    {
        $participants = $event->participants()
            ->where('users.id', '!=', $user->id)
            ->where('users.is_invisible', false)
            ->get();

        // Get recently declined/active suggestion user IDs to exclude
        $excludeIds = Suggestion::where('suggested_to_id', $user->id)
            ->where('event_id', $event->id)
            ->where(function ($q) {
                $q->where('status', 'declined')
                    ->where('updated_at', '>', now()->subHours(2));
            })
            ->orWhere(function ($q) use ($user, $event) {
                $q->where('suggested_to_id', $user->id)
                    ->where('event_id', $event->id)
                    ->where('status', 'pending');
            })
            ->pluck('suggested_user_id');

        return $participants
            ->reject(fn (User $p) => $excludeIds->contains($p->id))
            ->map(fn (User $p) => [
                'user' => $p,
                'score' => $this->score($user, $p, $event),
            ])
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    private function interestOverlap(User $a, User $b, Event $event): float
    {
        $tagsA = $a->interestTags()
            ->wherePivot('event_id', $event->id)
            ->pluck('interest_tags.id');

        $tagsB = $b->interestTags()
            ->wherePivot('event_id', $event->id)
            ->pluck('interest_tags.id');

        if ($tagsA->isEmpty() && $tagsB->isEmpty()) {
            return 0.0;
        }

        $shared = $tagsA->intersect($tagsB)->count();
        $max = max($tagsA->count(), $tagsB->count());

        return $max > 0 ? $shared / $max : 0.0;
    }

    private function contextMatch(User $a, User $b, Event $event): float
    {
        $score = 0.0;

        // Same session check
        $sessionA = SessionCheckIn::where('user_id', $a->id)
            ->whereNull('checked_out_at')
            ->pluck('event_session_id');

        $sessionB = SessionCheckIn::where('user_id', $b->id)
            ->whereNull('checked_out_at')
            ->pluck('event_session_id');

        if ($sessionA->intersect($sessionB)->isNotEmpty()) {
            $score += 0.5;
        }

        // Same booth check
        $pivotA = $a->events()->where('event_id', $event->id)->first()?->pivot;
        $pivotB = $b->events()->where('event_id', $event->id)->first()?->pivot;

        // Compare by booth visit records, not badge strings (avoids coupling to UI copy)
        $boothA = \App\Models\BoothVisit::where('user_id', $a->id)->whereNull('left_at')->pluck('booth_id');
        $boothB = \App\Models\BoothVisit::where('user_id', $b->id)->whereNull('left_at')->pluck('booth_id');

        if ($boothA->intersect($boothB)->isNotEmpty()) {
            $score += 0.3;
        }

        return min($score, 1.0);
    }

    private function availability(User $a, User $b, Event $event): float
    {
        $pivotA = $a->events()->where('event_id', $event->id)->first()?->pivot;
        $pivotB = $b->events()->where('event_id', $event->id)->first()?->pivot;

        $scoreA = self::STATUS_SCORES[$pivotA?->status ?? 'away'] ?? 0.0;
        $scoreB = self::STATUS_SCORES[$pivotB?->status ?? 'away'] ?? 0.0;

        return $scoreA * $scoreB;
    }
}
```

- [ ] **Step 4: Run tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=MatchingServiceTest`
Expected: All 4 tests PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/MatchingService.php tests/Feature/Services/MatchingServiceTest.php
git commit -m "feat: add MatchingService with interest overlap, context, and availability scoring"
```

---

## Task 3: Suggestion Service

**Files:**
- Create: `app/Services/SuggestionService.php`
- Create: `tests/Feature/Services/SuggestionServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Services/SuggestionServiceTest.php
<?php

use App\Models\Event;
use App\Models\InterestTag;
use App\Models\Suggestion;
use App\Models\User;
use App\Services\SuggestionService;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->service = app(SuggestionService::class);
});

it('generates suggestions for a user', function () {
    $tag = InterestTag::factory()->create();
    $user = User::factory()->create();
    $user->interestTags()->attach($tag, ['event_id' => $this->event->id]);
    $this->event->participants()->attach($user, ['participant_type' => 'physical', 'status' => 'available']);

    $others = User::factory(5)->create();
    foreach ($others as $other) {
        $other->interestTags()->attach($tag, ['event_id' => $this->event->id]);
        $this->event->participants()->attach($other, ['participant_type' => 'remote', 'status' => 'available']);
    }

    $suggestions = $this->service->generateForUser($user, $this->event);

    expect($suggestions)->toHaveCount(3)
        ->and($suggestions->first())->toBeInstanceOf(Suggestion::class);
});

it('limits active suggestions to 3', function () {
    $tag = InterestTag::factory()->create();
    $user = User::factory()->create();
    $user->interestTags()->attach($tag, ['event_id' => $this->event->id]);
    $this->event->participants()->attach($user, ['participant_type' => 'physical', 'status' => 'available']);

    // Create 3 existing active suggestions
    Suggestion::factory(3)->create([
        'suggested_to_id' => $user->id,
        'event_id' => $this->event->id,
        'status' => 'pending',
        'expires_at' => now()->addMinutes(10),
    ]);

    $others = User::factory(3)->create();
    foreach ($others as $other) {
        $other->interestTags()->attach($tag, ['event_id' => $this->event->id]);
        $this->event->participants()->attach($other, ['participant_type' => 'remote', 'status' => 'available']);
    }

    $suggestions = $this->service->generateForUser($user, $this->event);

    expect($suggestions)->toHaveCount(0);
});

it('declines a suggestion', function () {
    $suggestion = Suggestion::factory()->create(['status' => 'pending']);

    $this->service->decline($suggestion);

    expect($suggestion->fresh()->status)->toBe('declined');
});

it('accepts a suggestion', function () {
    $suggestion = Suggestion::factory()->create(['status' => 'pending']);

    $this->service->accept($suggestion);

    expect($suggestion->fresh()->status)->toBe('accepted');
});

it('does not re-suggest declined pairs within 2 hours', function () {
    $tag = InterestTag::factory()->create();
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $userA->interestTags()->attach($tag, ['event_id' => $this->event->id]);
    $userB->interestTags()->attach($tag, ['event_id' => $this->event->id]);

    $this->event->participants()->attach($userA, ['participant_type' => 'physical', 'status' => 'available']);
    $this->event->participants()->attach($userB, ['participant_type' => 'remote', 'status' => 'available']);

    Suggestion::factory()->create([
        'suggested_to_id' => $userA->id,
        'suggested_user_id' => $userB->id,
        'event_id' => $this->event->id,
        'status' => 'declined',
        'updated_at' => now(),
    ]);

    $suggestions = $this->service->generateForUser($userA, $this->event);

    $suggestedIds = $suggestions->pluck('suggested_user_id');
    expect($suggestedIds)->not->toContain($userB->id);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=SuggestionServiceTest`
Expected: FAIL

- [ ] **Step 3: Create SuggestionService**

```php
// app/Services/SuggestionService.php
<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Support\Collection;

class SuggestionService
{
    private const MAX_ACTIVE = 3;

    public function __construct(
        private MatchingService $matchingService,
    ) {}

    public function generateForUser(User $user, Event $event): Collection
    {
        $activeCount = Suggestion::forUser($user)
            ->where('event_id', $event->id)
            ->active()
            ->count();

        $slotsAvailable = self::MAX_ACTIVE - $activeCount;

        if ($slotsAvailable <= 0) {
            return collect();
        }

        $matches = $this->matchingService->topMatches($user, $event, $slotsAvailable);

        return $matches->map(fn (array $match) => Suggestion::create([
            'suggested_to_id' => $user->id,
            'suggested_user_id' => $match['user']->id,
            'event_id' => $event->id,
            'score' => $match['score'],
            'reason' => $this->buildReason($user, $match['user'], $event),
            'trigger' => 'interest_overlap',
            'expires_at' => now()->addMinutes(15),
        ]));
    }

    public function decline(Suggestion $suggestion): void
    {
        $suggestion->update(['status' => 'declined']);
    }

    public function accept(Suggestion $suggestion): void
    {
        $suggestion->update(['status' => 'accepted']);
    }

    private function buildReason(User $a, User $b, Event $event): string
    {
        $tagsA = $a->interestTags()->wherePivot('event_id', $event->id)->pluck('name');
        $tagsB = $b->interestTags()->wherePivot('event_id', $event->id)->pluck('name');
        $shared = $tagsA->intersect($tagsB);

        if ($shared->isNotEmpty()) {
            return "You both tagged: {$shared->implode(', ')}";
        }

        return 'Suggested based on availability and context';
    }
}
```

- [ ] **Step 4: Run tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=SuggestionServiceTest`
Expected: All 5 tests PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/SuggestionService.php tests/Feature/Services/SuggestionServiceTest.php
git commit -m "feat: add SuggestionService with generation, lifecycle, and anti-repeat logic"
```

---

## Task 4: Serendipity Mode

**Files:**
- Modify: `app/Services/MatchingService.php`
- Modify: `app/Services/SuggestionService.php`
- Create: `tests/Feature/Services/SerendipityTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Services/SerendipityTest.php
<?php

use App\Models\Event;
use App\Models\InterestTag;
use App\Models\User;
use App\Services\MatchingService;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->service = app(MatchingService::class);
});

it('returns a serendipity match with zero tag overlap', function () {
    $tagA = InterestTag::factory()->create(['name' => 'Zero Trust']);
    $tagB = InterestTag::factory()->create(['name' => 'AI/ML']);

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $userA->interestTags()->attach($tagA, ['event_id' => $this->event->id]);
    $userB->interestTags()->attach($tagB, ['event_id' => $this->event->id]);

    $this->event->participants()->attach($userA, ['participant_type' => 'physical', 'status' => 'available']);
    $this->event->participants()->attach($userB, ['participant_type' => 'remote', 'status' => 'available']);

    $match = $this->service->serendipityMatch($userA, $this->event);

    expect($match)->not->toBeNull()
        ->and($match->id)->toBe($userB->id);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=SerendipityTest`
Expected: FAIL

- [ ] **Step 3: Add serendipityMatch method to MatchingService**

Add to `app/Services/MatchingService.php`:

```php
public function serendipityMatch(User $user, Event $event): ?User
{
    $userTagIds = $user->interestTags()
        ->wherePivot('event_id', $event->id)
        ->pluck('interest_tags.id');

    return $event->participants()
        ->where('users.id', '!=', $user->id)
        ->where('users.is_invisible', false)
        ->wherePivot('status', '!=', 'busy')
        ->get()
        ->filter(function (User $candidate) use ($userTagIds, $event) {
            $candidateTagIds = $candidate->interestTags()
                ->wherePivot('event_id', $event->id)
                ->pluck('interest_tags.id');

            return $candidateTagIds->intersect($userTagIds)->isEmpty();
        })
        ->sortByDesc(function (User $candidate) use ($event) {
            return self::STATUS_SCORES[$candidate->pivot->status ?? 'away'] ?? 0;
        })
        ->first();
}
```

- [ ] **Step 4: Run tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=SerendipityTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/MatchingService.php tests/Feature/Services/SerendipityTest.php
git commit -m "feat: add serendipity mode — matches users with zero tag overlap"
```

---

## Task 5: Suggestion Controller & Search

**Files:**
- Create: `app/Http/Controllers/SuggestionController.php`
- Create: `app/Http/Controllers/SearchController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Http/SuggestionControllerTest.php`
- Create: `tests/Feature/Http/SearchControllerTest.php`

- [ ] **Step 1: Write the failing tests**

```php
// tests/Feature/Http/SuggestionControllerTest.php
<?php

use App\Models\Event;
use App\Models\InterestTag;
use App\Models\Suggestion;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->user = User::factory()->create();
    $tag = InterestTag::factory()->create();
    $this->user->interestTags()->attach($tag, ['event_id' => $this->event->id]);
    $this->event->participants()->attach($this->user, ['participant_type' => 'physical', 'status' => 'available']);

    $this->others = User::factory(5)->create();
    foreach ($this->others as $other) {
        $other->interestTags()->attach($tag, ['event_id' => $this->event->id]);
        $this->event->participants()->attach($other, ['participant_type' => 'remote', 'status' => 'available']);
    }
});

it('returns suggestions for the current user', function () {
    $response = $this->actingAs($this->user)
        ->get(route('event.suggestions', $this->event));

    $response->assertOk()
        ->assertJsonStructure(['data' => [['id', 'suggested_user', 'score', 'reason', 'expires_at']]]);
});

it('declines a suggestion', function () {
    $suggestion = Suggestion::factory()->create([
        'suggested_to_id' => $this->user->id,
        'event_id' => $this->event->id,
    ]);

    $response = $this->actingAs($this->user)
        ->patch(route('event.suggestions.decline', [$this->event, $suggestion]));

    $response->assertOk();
    expect($suggestion->fresh()->status)->toBe('declined');
});

it('accepts a suggestion', function () {
    $suggestion = Suggestion::factory()->create([
        'suggested_to_id' => $this->user->id,
        'event_id' => $this->event->id,
    ]);

    $response = $this->actingAs($this->user)
        ->patch(route('event.suggestions.accept', [$this->event, $suggestion]));

    $response->assertOk();
    expect($suggestion->fresh()->status)->toBe('accepted');
});
```

```php
// tests/Feature/Http/SearchControllerTest.php
<?php

use App\Models\Event;
use App\Models\InterestTag;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->user = User::factory()->create();
    $this->event->participants()->attach($this->user, ['participant_type' => 'physical', 'status' => 'available']);
});

it('searches participants by name', function () {
    $target = User::factory()->create(['name' => 'Lena Fischer']);
    $this->event->participants()->attach($target, ['participant_type' => 'remote', 'status' => 'available']);

    $response = $this->actingAs($this->user)
        ->get(route('event.search', ['event' => $this->event, 'q' => 'Lena']));

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('searches participants by company', function () {
    $target = User::factory()->create(['company' => 'CyberDefense AG']);
    $this->event->participants()->attach($target, ['participant_type' => 'remote', 'status' => 'available']);

    $response = $this->actingAs($this->user)
        ->get(route('event.search', ['event' => $this->event, 'q' => 'CyberDefense']));

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('excludes invisible participants from search', function () {
    $invisible = User::factory()->create(['name' => 'Hidden Person', 'is_invisible' => true]);
    $this->event->participants()->attach($invisible, ['participant_type' => 'remote', 'status' => 'available']);

    $response = $this->actingAs($this->user)
        ->get(route('event.search', ['event' => $this->event, 'q' => 'Hidden']));

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter="SuggestionControllerTest|SearchControllerTest"`
Expected: FAIL

- [ ] **Step 3: Create SuggestionController**

Run: `php artisan make:controller SuggestionController --no-interaction`

```php
// app/Http/Controllers/SuggestionController.php
<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Suggestion;
use App\Services\SuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuggestionController extends Controller
{
    public function index(Request $request, Event $event, SuggestionService $suggestionService): JsonResponse
    {
        $user = $request->user();

        // Generate new suggestions if needed
        $suggestionService->generateForUser($user, $event);

        $suggestions = Suggestion::forUser($user)
            ->where('event_id', $event->id)
            ->active()
            ->with('suggestedUser')
            ->orderByDesc('score')
            ->get()
            ->map(fn (Suggestion $s) => [
                'id' => $s->id,
                'suggested_user' => [
                    'id' => $s->suggestedUser->id,
                    'name' => $s->suggestedUser->name,
                    'company' => $s->suggestedUser->company,
                    'participant_type' => $s->suggestedUser->events()
                        ->where('event_id', $event->id)
                        ->first()?->pivot?->participant_type,
                ],
                'score' => $s->score,
                'reason' => $s->reason,
                'expires_at' => $s->expires_at->toISOString(),
            ]);

        return response()->json(['data' => $suggestions]);
    }

    public function decline(Request $request, Event $event, Suggestion $suggestion, SuggestionService $suggestionService): JsonResponse
    {
        abort_unless($suggestion->suggested_to_id === $request->user()->id, 403);

        $suggestionService->decline($suggestion);

        return response()->json(['message' => 'Suggestion declined']);
    }

    public function accept(Request $request, Event $event, Suggestion $suggestion, SuggestionService $suggestionService): JsonResponse
    {
        abort_unless($suggestion->suggested_to_id === $request->user()->id, 403);

        $suggestionService->accept($suggestion);

        return response()->json(['message' => 'Suggestion accepted']);
    }
}
```

- [ ] **Step 4: Create SearchController**

Run: `php artisan make:controller SearchController --no-interaction`

```php
// app/Http/Controllers/SearchController.php
<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request, Event $event): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        $participants = $event->participants()
            ->where('users.is_invisible', false)
            ->where('users.id', '!=', $request->user()->id)
            ->where(function ($q) use ($query) {
                $q->where('users.name', 'like', "%{$query}%")
                    ->orWhere('users.company', 'like', "%{$query}%");
            })
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'company' => $p->company,
                'participant_type' => $p->pivot->participant_type,
                'status' => $p->pivot->status,
            ]);

        return response()->json(['data' => $participants]);
    }
}
```

- [ ] **Step 5: Add routes**

Add to `routes/web.php` inside the auth middleware group:

```php
use App\Http\Controllers\SuggestionController;
use App\Http\Controllers\SearchController;

Route::get('/event/{event:slug}/suggestions', [SuggestionController::class, 'index'])->name('event.suggestions');
Route::patch('/event/{event:slug}/suggestions/{suggestion}/decline', [SuggestionController::class, 'decline'])->name('event.suggestions.decline');
Route::patch('/event/{event:slug}/suggestions/{suggestion}/accept', [SuggestionController::class, 'accept'])->name('event.suggestions.accept');
Route::get('/event/{event:slug}/search', SearchController::class)->name('event.search');
```

- [ ] **Step 6: Run tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter="SuggestionControllerTest|SearchControllerTest"`
Expected: All 6 tests PASS

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/SuggestionController.php app/Http/Controllers/SearchController.php routes/web.php tests/Feature/Http/SuggestionControllerTest.php tests/Feature/Http/SearchControllerTest.php
git commit -m "feat: add suggestion and search controllers with routes"
```

---

## Task 6: Expire Suggestions Command

**Files:**
- Create: `app/Console/Commands/ExpireSuggestions.php`
- Create: `tests/Feature/Commands/ExpireSuggestionsTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Commands/ExpireSuggestionsTest.php
<?php

use App\Models\Suggestion;

it('expires stale suggestions', function () {
    $stale = Suggestion::factory()->create([
        'status' => 'pending',
        'expires_at' => now()->subMinute(),
    ]);

    $fresh = Suggestion::factory()->create([
        'status' => 'pending',
        'expires_at' => now()->addMinutes(10),
    ]);

    $this->artisan('suggestions:expire')->assertSuccessful();

    expect($stale->fresh()->status)->toBe('expired')
        ->and($fresh->fresh()->status)->toBe('pending');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=ExpireSuggestionsTest`
Expected: FAIL

- [ ] **Step 3: Create the command**

Run: `php artisan make:command ExpireSuggestions --no-interaction`

```php
// app/Console/Commands/ExpireSuggestions.php
<?php

namespace App\Console\Commands;

use App\Models\Suggestion;
use Illuminate\Console\Command;

class ExpireSuggestions extends Command
{
    protected $signature = 'suggestions:expire';
    protected $description = 'Expire stale suggestions past their TTL';

    public function handle(): int
    {
        $count = Suggestion::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        $this->info("Expired {$count} suggestions.");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Run tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=ExpireSuggestionsTest`
Expected: PASS

- [ ] **Step 5: Schedule the command**

Add to `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('suggestions:expire')->everyMinute();
```

- [ ] **Step 6: Commit**

```bash
git add app/Console/Commands/ExpireSuggestions.php tests/Feature/Commands/ExpireSuggestionsTest.php routes/console.php
git commit -m "feat: add suggestions:expire command with scheduler"
```

---

## Task 7: Run Full Suite & Lint

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
