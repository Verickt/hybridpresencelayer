# Plan 1: Data Model & Auth — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create the complete database schema, Eloquent models with factories/seeders, and magic link authentication for the Hybrid Presence Platform.

**Architecture:** Extend the existing User model with participant fields (make `password` nullable for magic-link-only users). Create all domain models (Event, Session, Booth, Ping, Connection, etc.) with proper relationships. Replace password-based auth with magic link flow using a custom controller (disable Fortify registration, password reset, and 2FA features). Magic link tokens are stored as SHA-256 hashes for security. Connections store user IDs in sorted order (lower first) for unique constraint. Organizer and booth staff are roles on the User model, not separate models.

**Tech Stack:** Laravel 13, Fortify v1, Pest v4, SQLite (dev), Inertia v3

## TDD Standard

- Start each task with failing tests before implementation.
- Do not stop at a single happy-path test. Cover the relevant validation, authorization, not-found, expired, duplicate, rate-limit, and state-transition failure paths for the task.
- For Inertia endpoints, add endpoint tests with `assertInertia` for component names, critical props, flash data, and hidden/sensitive fields.
- For auth and onboarding flows, add Pest browser tests for at least one successful path and one rejection path in the real browser.
- If Fortify features are disabled, update the generated Wayfinder route usage, Inertia pages, and starter-kit auth tests that reference those routes so the frontend build and test suite stay green.

---

## File Structure

### Migrations (database/migrations/)
```
create_events_table.php
create_interest_tags_table.php
create_event_interest_tag_table.php
create_sessions_table.php
create_booths_table.php
create_booth_staff_table.php
add_participant_fields_to_users_table.php
create_event_user_table.php
create_user_interest_tag_table.php
create_pings_table.php
create_connections_table.php
create_messages_table.php
create_session_check_ins_table.php
create_booth_visits_table.php
create_session_reactions_table.php
create_session_questions_table.php
create_session_question_votes_table.php
create_magic_links_table.php
create_icebreaker_questions_table.php
create_contact_cards_table.php
```

### Models (app/Models/)
```
Event.php
InterestTag.php
Session.php
Booth.php
Ping.php
Connection.php
Message.php
SessionCheckIn.php
BoothVisit.php
SessionReaction.php
SessionQuestion.php
SessionQuestionVote.php
MagicLink.php
IcebreakerQuestion.php
ContactCard.php
```

### Factories (database/factories/)
One factory per model above.

### Seeders (database/seeders/)
```
DemoEventSeeder.php  — creates a full demo event with sessions, booths, tags, participants
```

### Auth (app/Actions/, app/Http/Controllers/Auth/)
```
app/Actions/CreateMagicLink.php
app/Actions/AuthenticateViaMagicLink.php
app/Http/Controllers/Auth/MagicLinkController.php
app/Notifications/MagicLinkNotification.php
```

---

## Task 1: Event Model

**Files:**
- Create: `database/migrations/xxxx_create_events_table.php`
- Create: `app/Models/Event.php`
- Create: `database/factories/EventFactory.php`
- Create: `tests/Feature/Models/EventTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Models/EventTest.php
<?php

use App\Models\Event;
use App\Models\User;

it('can create an event', function () {
    $event = Event::factory()->create();

    expect($event)->toBeInstanceOf(Event::class)
        ->and($event->name)->toBeString()
        ->and($event->slug)->toBeString()
        ->and($event->starts_at)->toBeInstanceOf(\Carbon\Carbon::class)
        ->and($event->ends_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

it('belongs to an organizer', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    expect($event->organizer)->toBeInstanceOf(User::class)
        ->and($event->organizer->id)->toBe($organizer->id);
});

it('generates a slug from the name', function () {
    $event = Event::factory()->create(['name' => 'BSI Cyber Security Conference 2026']);

    expect($event->slug)->toBe('bsi-cyber-security-conference-2026');
});

it('knows if it is currently live', function () {
    $live = Event::factory()->create([
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
    ]);

    $past = Event::factory()->create([
        'starts_at' => now()->subDays(2),
        'ends_at' => now()->subDay(),
    ]);

    expect($live->isLive())->toBeTrue()
        ->and($past->isLive())->toBeFalse();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=EventTest`
Expected: FAIL — Event model/table doesn't exist

- [ ] **Step 3: Create migration**

Run: `php artisan make:migration create_events_table --no-interaction`

Then edit the migration:

```php
Schema::create('events', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organizer_id')->constrained('users')->cascadeOnDelete();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->string('venue')->nullable();
    $table->string('streaming_url')->nullable();
    $table->string('logo_path')->nullable();
    $table->string('theme_color', 7)->default('#3B82F6');
    $table->dateTime('starts_at');
    $table->dateTime('ends_at');
    $table->boolean('allow_open_registration')->default(false);
    $table->timestamps();
});
```

- [ ] **Step 4: Create model**

Run: `php artisan make:class App/Models/Event --no-interaction`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'allow_open_registration' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Event $event) {
            $event->slug ??= Str::slug($event->name);
        });
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function isLive(): bool
    {
        return now()->between($this->starts_at, $this->ends_at);
    }
}
```

- [ ] **Step 5: Create factory**

Run: `php artisan make:factory EventFactory --no-interaction`

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    public function definition(): array
    {
        $startsAt = $this->faker->dateTimeBetween('-1 day', '+7 days');

        return [
            'organizer_id' => User::factory(),
            'name' => $this->faker->words(4, true) . ' Conference',
            'description' => $this->faker->paragraph(),
            'venue' => $this->faker->address(),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+8 hours'),
            'allow_open_registration' => false,
        ];
    }

    public function live(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(8),
        ]);
    }

    public function past(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDay(),
        ]);
    }
}
```

- [ ] **Step 6: Run migration and tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=EventTest`
Expected: All 4 tests PASS

- [ ] **Step 7: Commit**

```bash
git add database/migrations/*create_events* app/Models/Event.php database/factories/EventFactory.php tests/Feature/Models/EventTest.php
git commit -m "feat: add Event model with migration, factory, and tests"
```

---

## Task 2: Interest Tags

**Files:**
- Create: `database/migrations/xxxx_create_interest_tags_table.php`
- Create: `database/migrations/xxxx_create_event_interest_tag_table.php`
- Create: `app/Models/InterestTag.php`
- Create: `database/factories/InterestTagFactory.php`
- Create: `tests/Feature/Models/InterestTagTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Models/InterestTagTest.php
<?php

use App\Models\Event;
use App\Models\InterestTag;

it('can create an interest tag', function () {
    $tag = InterestTag::factory()->create();

    expect($tag->name)->toBeString()
        ->and($tag->slug)->toBeString();
});

it('belongs to many events', function () {
    $event = Event::factory()->create();
    $tag = InterestTag::factory()->create();

    $event->interestTags()->attach($tag);

    expect($tag->events)->toHaveCount(1)
        ->and($tag->events->first()->id)->toBe($event->id);
});

it('generates slug from name', function () {
    $tag = InterestTag::factory()->create(['name' => 'Zero Trust']);

    expect($tag->slug)->toBe('zero-trust');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=InterestTagTest`
Expected: FAIL

- [ ] **Step 3: Create migration for interest_tags**

Run: `php artisan make:migration create_interest_tags_table --no-interaction`

```php
Schema::create('interest_tags', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->timestamps();
});
```

- [ ] **Step 4: Create pivot migration for event_interest_tag**

Run: `php artisan make:migration create_event_interest_tag_table --no-interaction`

```php
Schema::create('event_interest_tag', function (Blueprint $table) {
    $table->id();
    $table->foreignId('event_id')->constrained()->cascadeOnDelete();
    $table->foreignId('interest_tag_id')->constrained()->cascadeOnDelete();
    $table->unique(['event_id', 'interest_tag_id']);
});
```

- [ ] **Step 5: Create model and factory**

```php
// app/Models/InterestTag.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class InterestTag extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (InterestTag $tag) {
            $tag->slug ??= Str::slug($tag->name);
        });
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class);
    }
}
```

```php
// database/factories/InterestTagFactory.php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class InterestTagFactory extends Factory
{
    private static array $tags = [
        'Zero Trust', 'Cloud Migration', 'DevOps', 'AI/ML', 'Startup',
        'Enterprise', 'Cybersecurity', 'Data Privacy', 'IoT', 'Blockchain',
        'Remote Work', 'Leadership', 'Open Source', 'Edge Computing', 'API Design',
        'Platform Engineering', 'Observability', 'FinTech', 'HealthTech', 'GreenTech',
    ];

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement(self::$tags),
        ];
    }
}
```

- [ ] **Step 6: Add interestTags relationship to Event model**

```php
// Add to app/Models/Event.php
public function interestTags(): BelongsToMany
{
    return $this->belongsToMany(InterestTag::class);
}
```

- [ ] **Step 7: Run migration and tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=InterestTagTest`
Expected: All 3 tests PASS

- [ ] **Step 8: Commit**

```bash
git add database/migrations/*interest_tag* app/Models/InterestTag.php database/factories/InterestTagFactory.php tests/Feature/Models/InterestTagTest.php app/Models/Event.php
git commit -m "feat: add InterestTag model with event pivot"
```

---

## Task 3: Extend User Model for Participants

**Files:**
- Create: `database/migrations/xxxx_add_participant_fields_to_users_table.php`
- Create: `database/migrations/xxxx_create_event_user_table.php`
- Create: `database/migrations/xxxx_create_user_interest_tag_table.php`
- Modify: `app/Models/User.php`
- Modify: `database/factories/UserFactory.php`
- Create: `tests/Feature/Models/ParticipantTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Models/ParticipantTest.php
<?php

use App\Models\Event;
use App\Models\InterestTag;
use App\Models\User;

it('has participant fields', function () {
    $user = User::factory()->create([
        'company' => 'Acme Corp',
        'role_title' => 'CTO',
        'intent' => 'Looking for cloud migration partners',
    ]);

    expect($user->company)->toBe('Acme Corp')
        ->and($user->role_title)->toBe('CTO')
        ->and($user->intent)->toBe('Looking for cloud migration partners');
});

it('can be an organizer', function () {
    $user = User::factory()->organizer()->create();

    expect($user->is_organizer)->toBeTrue();
});

it('participates in events with a type', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    $user->events()->attach($event, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);

    $pivot = $user->events->first()->pivot;

    expect($pivot->participant_type)->toBe('physical')
        ->and($pivot->status)->toBe('available');
});

it('has interest tags scoped to an event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $tag = InterestTag::factory()->create();

    $user->interestTags()->attach($tag, ['event_id' => $event->id]);

    expect($user->interestTags)->toHaveCount(1)
        ->and($user->interestTags->first()->id)->toBe($tag->id);
});

it('can have an icebreaker answer', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    $user->events()->attach($event, [
        'participant_type' => 'remote',
        'status' => 'available',
        'icebreaker_answer' => "What's the boldest tech bet you've made this year?",
    ]);

    expect($user->events->first()->pivot->icebreaker_answer)->toBeString();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=ParticipantTest`
Expected: FAIL

- [ ] **Step 3: Create migration for user participant fields**

Run: `php artisan make:migration add_participant_fields_to_users_table --no-interaction`

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('company')->nullable()->after('email');
    $table->string('role_title')->nullable()->after('company');
    $table->string('intent', 200)->nullable()->after('role_title');
    $table->string('linkedin_url')->nullable()->after('intent');
    $table->string('phone')->nullable()->after('linkedin_url');
    $table->boolean('is_organizer')->default(false)->after('phone');
    $table->boolean('is_invisible')->default(false)->after('is_organizer');
});

// Make password nullable for magic-link-only users
Schema::table('users', function (Blueprint $table) {
    $table->string('password')->nullable()->change();
});
```

- [ ] **Step 4: Create event_user pivot migration**

Run: `php artisan make:migration create_event_user_table --no-interaction`

```php
Schema::create('event_user', function (Blueprint $table) {
    $table->id();
    $table->foreignId('event_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->enum('participant_type', ['physical', 'remote']);
    $table->string('status')->default('available');
    $table->string('context_badge')->nullable();
    $table->string('icebreaker_answer')->nullable();
    $table->boolean('open_to_call')->default(false);
    $table->boolean('available_after_session')->default(false);
    $table->timestamp('last_active_at')->nullable();
    $table->unique(['event_id', 'user_id']);
    $table->timestamps();
});
```

- [ ] **Step 5: Create user_interest_tag pivot migration**

Run: `php artisan make:migration create_user_interest_tag_table --no-interaction`

```php
Schema::create('user_interest_tag', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('interest_tag_id')->constrained()->cascadeOnDelete();
    $table->foreignId('event_id')->constrained()->cascadeOnDelete();
    $table->unique(['user_id', 'interest_tag_id', 'event_id']);
});
```

- [ ] **Step 6: Update User model with relationships**

Add to `app/Models/User.php`:

```php
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// Add to $casts or casts():
'is_organizer' => 'boolean',
'is_invisible' => 'boolean',

// Add relationships:
public function events(): BelongsToMany
{
    return $this->belongsToMany(Event::class)
        ->withPivot([
            'participant_type', 'status', 'context_badge',
            'icebreaker_answer', 'open_to_call',
            'available_after_session', 'last_active_at',
        ])
        ->withTimestamps();
}

public function interestTags(): BelongsToMany
{
    return $this->belongsToMany(InterestTag::class)
        ->withPivot('event_id');
}

public function organizedEvents(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(Event::class, 'organizer_id');
}
```

- [ ] **Step 7: Update UserFactory with participant states**

Add to `database/factories/UserFactory.php`:

```php
public function organizer(): static
{
    return $this->state(fn () => [
        'is_organizer' => true,
    ]);
}

public function withProfile(): static
{
    return $this->state(fn () => [
        'company' => $this->faker->company(),
        'role_title' => $this->faker->jobTitle(),
        'intent' => $this->faker->sentence(),
    ]);
}
```

- [ ] **Step 8: Run migration and tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=ParticipantTest`
Expected: All 5 tests PASS

- [ ] **Step 9: Commit**

```bash
git add database/migrations/*participant* database/migrations/*event_user* database/migrations/*user_interest_tag* app/Models/User.php database/factories/UserFactory.php tests/Feature/Models/ParticipantTest.php
git commit -m "feat: extend User model with participant fields, event pivot, and interest tags"
```

---

## Task 4: Session Model

**Files:**
- Create: `database/migrations/xxxx_create_sessions_table.php`
- Create: `app/Models/Session.php` (note: use `EventSession` to avoid PHP conflict)
- Create: `database/factories/EventSessionFactory.php`
- Create: `tests/Feature/Models/EventSessionTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Models/EventSessionTest.php
<?php

use App\Models\Event;
use App\Models\EventSession;
use App\Models\User;

it('belongs to an event', function () {
    $event = Event::factory()->create();
    $session = EventSession::factory()->create(['event_id' => $event->id]);

    expect($session->event->id)->toBe($event->id);
});

it('has a time range', function () {
    $session = EventSession::factory()->create();

    expect($session->starts_at)->toBeInstanceOf(\Carbon\Carbon::class)
        ->and($session->ends_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

it('knows if it is currently happening', function () {
    $live = EventSession::factory()->create([
        'starts_at' => now()->subMinutes(10),
        'ends_at' => now()->addMinutes(50),
    ]);

    $past = EventSession::factory()->create([
        'starts_at' => now()->subHours(3),
        'ends_at' => now()->subHours(2),
    ]);

    expect($live->isLive())->toBeTrue()
        ->and($past->isLive())->toBeFalse();
});

it('can have Q&A enabled or disabled', function () {
    $session = EventSession::factory()->create(['qa_enabled' => false]);

    expect($session->qa_enabled)->toBeFalse();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=EventSessionTest`
Expected: FAIL

- [ ] **Step 3: Create migration**

Run: `php artisan make:migration create_event_sessions_table --no-interaction`

```php
Schema::create('event_sessions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('event_id')->constrained()->cascadeOnDelete();
    $table->string('title');
    $table->text('description')->nullable();
    $table->string('speaker')->nullable();
    $table->string('room')->nullable();
    $table->dateTime('starts_at');
    $table->dateTime('ends_at');
    $table->boolean('qa_enabled')->default(true);
    $table->boolean('reactions_enabled')->default(true);
    $table->timestamps();
});
```

- [ ] **Step 4: Create model**

```php
// app/Models/EventSession.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventSession extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'qa_enabled' => 'boolean',
            'reactions_enabled' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function isLive(): bool
    {
        return now()->between($this->starts_at, $this->ends_at);
    }
}
```

- [ ] **Step 5: Create factory**

```php
// database/factories/EventSessionFactory.php
<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventSessionFactory extends Factory
{
    public function definition(): array
    {
        $startsAt = $this->faker->dateTimeBetween('now', '+7 days');

        return [
            'event_id' => Event::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'speaker' => $this->faker->name(),
            'room' => 'Room ' . $this->faker->randomElement(['A', 'B', 'C', 'Main Stage']),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+45 minutes'),
            'qa_enabled' => true,
            'reactions_enabled' => true,
        ];
    }

    public function live(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->subMinutes(10),
            'ends_at' => now()->addMinutes(50),
        ]);
    }
}
```

- [ ] **Step 6: Add sessions relationship to Event model**

```php
// Add to app/Models/Event.php
public function sessions(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(EventSession::class);
}
```

- [ ] **Step 7: Run migration and tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=EventSessionTest`
Expected: All 4 tests PASS

- [ ] **Step 8: Commit**

```bash
git add database/migrations/*event_sessions* app/Models/EventSession.php database/factories/EventSessionFactory.php tests/Feature/Models/EventSessionTest.php app/Models/Event.php
git commit -m "feat: add EventSession model with migration, factory, and tests"
```

---

## Task 5: Booth Model

**Files:**
- Create: `database/migrations/xxxx_create_booths_table.php`
- Create: `database/migrations/xxxx_create_booth_staff_table.php`
- Create: `app/Models/Booth.php`
- Create: `database/factories/BoothFactory.php`
- Create: `tests/Feature/Models/BoothTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Models/BoothTest.php
<?php

use App\Models\Booth;
use App\Models\Event;
use App\Models\InterestTag;
use App\Models\User;

it('belongs to an event', function () {
    $event = Event::factory()->create();
    $booth = Booth::factory()->create(['event_id' => $event->id]);

    expect($booth->event->id)->toBe($event->id);
});

it('has staff members', function () {
    $booth = Booth::factory()->create();
    $staff = User::factory()->create();

    $booth->staff()->attach($staff);

    expect($booth->staff)->toHaveCount(1)
        ->and($booth->staff->first()->id)->toBe($staff->id);
});

it('has interest tags', function () {
    $booth = Booth::factory()->create();
    $tag = InterestTag::factory()->create();

    $booth->interestTags()->attach($tag);

    expect($booth->interestTags)->toHaveCount(1);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=BoothTest`
Expected: FAIL

- [ ] **Step 3: Create booths migration**

Run: `php artisan make:migration create_booths_table --no-interaction`

```php
Schema::create('booths', function (Blueprint $table) {
    $table->id();
    $table->foreignId('event_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->string('company');
    $table->text('description')->nullable();
    $table->text('content_links')->nullable();
    $table->timestamps();
});
```

- [ ] **Step 4: Create booth_staff pivot migration**

Run: `php artisan make:migration create_booth_staff_table --no-interaction`

```php
Schema::create('booth_staff', function (Blueprint $table) {
    $table->id();
    $table->foreignId('booth_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->unique(['booth_id', 'user_id']);
});
```

- [ ] **Step 5: Create booth_interest_tag pivot migration**

Run: `php artisan make:migration create_booth_interest_tag_table --no-interaction`

```php
Schema::create('booth_interest_tag', function (Blueprint $table) {
    $table->id();
    $table->foreignId('booth_id')->constrained()->cascadeOnDelete();
    $table->foreignId('interest_tag_id')->constrained()->cascadeOnDelete();
    $table->unique(['booth_id', 'interest_tag_id']);
});
```

- [ ] **Step 6: Create model and factory**

```php
// app/Models/Booth.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Booth extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'content_links' => 'array',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'booth_staff');
    }

    public function interestTags(): BelongsToMany
    {
        return $this->belongsToMany(InterestTag::class);
    }
}
```

```php
// database/factories/BoothFactory.php
<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class BoothFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => $this->faker->company() . ' Booth',
            'company' => $this->faker->company(),
            'description' => $this->faker->paragraph(),
            'content_links' => [
                ['label' => 'Website', 'url' => $this->faker->url()],
                ['label' => 'Product Sheet', 'url' => $this->faker->url()],
            ],
        ];
    }
}
```

- [ ] **Step 7: Add booths relationship to Event model**

```php
// Add to app/Models/Event.php
public function booths(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(Booth::class);
}
```

- [ ] **Step 8: Run migration and tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=BoothTest`
Expected: All 3 tests PASS

- [ ] **Step 9: Commit**

```bash
git add database/migrations/*booth* app/Models/Booth.php database/factories/BoothFactory.php tests/Feature/Models/BoothTest.php app/Models/Event.php
git commit -m "feat: add Booth model with staff pivot, interest tags, and tests"
```

---

## Task 6: Ping & Connection Models

**Files:**
- Create: `database/migrations/xxxx_create_pings_table.php`
- Create: `database/migrations/xxxx_create_connections_table.php`
- Create: `app/Models/Ping.php`
- Create: `app/Models/Connection.php`
- Create: `database/factories/PingFactory.php`
- Create: `database/factories/ConnectionFactory.php`
- Create: `tests/Feature/Models/PingTest.php`
- Create: `tests/Feature/Models/ConnectionTest.php`

- [ ] **Step 1: Write Ping test**

```php
// tests/Feature/Models/PingTest.php
<?php

use App\Models\Event;
use App\Models\Ping;
use App\Models\User;

it('has a sender and receiver', function () {
    $sender = User::factory()->create();
    $receiver = User::factory()->create();
    $event = Event::factory()->create();

    $ping = Ping::factory()->create([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'event_id' => $event->id,
    ]);

    expect($ping->sender->id)->toBe($sender->id)
        ->and($ping->receiver->id)->toBe($receiver->id)
        ->and($ping->event->id)->toBe($event->id);
});

it('expires after 30 minutes', function () {
    $fresh = Ping::factory()->create(['created_at' => now()]);
    $expired = Ping::factory()->create(['created_at' => now()->subMinutes(31)]);

    expect($fresh->isExpired())->toBeFalse()
        ->and($expired->isExpired())->toBeTrue();
});

it('scopes to non-expired pings', function () {
    Ping::factory()->create(['created_at' => now()]);
    Ping::factory()->create(['created_at' => now()->subMinutes(31)]);

    expect(Ping::active()->count())->toBe(1);
});
```

- [ ] **Step 2: Write Connection test**

```php
// tests/Feature/Models/ConnectionTest.php
<?php

use App\Models\Connection;
use App\Models\Event;
use App\Models\User;

it('connects two users in an event', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $event = Event::factory()->create();

    $connection = Connection::factory()->create([
        'user_a_id' => $userA->id,
        'user_b_id' => $userB->id,
        'event_id' => $event->id,
        'context' => 'Matched during Zero Trust keynote',
    ]);

    expect($connection->userA->id)->toBe($userA->id)
        ->and($connection->userB->id)->toBe($userB->id)
        ->and($connection->context)->toBe('Matched during Zero Trust keynote');
});

it('tracks cross-world connections', function () {
    $connection = Connection::factory()->create(['is_cross_world' => true]);

    expect($connection->is_cross_world)->toBeTrue();
});
```

- [ ] **Step 3: Run tests to verify they fail**

Run: `php artisan test --compact --filter="PingTest|ConnectionTest"`
Expected: FAIL

- [ ] **Step 4: Create pings migration**

Run: `php artisan make:migration create_pings_table --no-interaction`

```php
Schema::create('pings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('event_id')->constrained()->cascadeOnDelete();
    $table->enum('status', ['pending', 'matched', 'ignored', 'expired'])->default('pending');
    $table->timestamps();

    $table->index(['sender_id', 'receiver_id', 'event_id']);
});
```

- [ ] **Step 5: Create connections migration**

Run: `php artisan make:migration create_connections_table --no-interaction`

```php
Schema::create('connections', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_a_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('user_b_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('event_id')->constrained()->cascadeOnDelete();
    $table->string('context')->nullable();
    $table->boolean('is_cross_world')->default(false);
    $table->timestamps();

    $table->unique(['user_a_id', 'user_b_id', 'event_id']);
});
```

- [ ] **Step 6: Create Ping model and factory**

```php
// app/Models/Ping.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ping extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function isExpired(): bool
    {
        return $this->created_at->diffInMinutes(now()) > 30;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('created_at', '>', now()->subMinutes(30))
            ->where('status', 'pending');
    }
}
```

```php
// database/factories/PingFactory.php
<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sender_id' => User::factory(),
            'receiver_id' => User::factory(),
            'event_id' => Event::factory(),
            'status' => 'pending',
        ];
    }
}
```

- [ ] **Step 7: Create Connection model and factory**

```php
// app/Models/Connection.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Connection extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_cross_world' => 'boolean',
        ];
    }

    // Store user IDs in sorted order (lower first) for unique constraint
    protected static function booted(): void
    {
        static::creating(function (Connection $connection) {
            if ($connection->user_a_id > $connection->user_b_id) {
                [$connection->user_a_id, $connection->user_b_id] = [$connection->user_b_id, $connection->user_a_id];
            }
        });
    }

    public function userA(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_a_id');
    }

    public function userB(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_b_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
```

```php
// database/factories/ConnectionFactory.php
<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConnectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_a_id' => User::factory(),
            'user_b_id' => User::factory(),
            'event_id' => Event::factory(),
            'context' => $this->faker->sentence(),
            'is_cross_world' => $this->faker->boolean(20),
        ];
    }
}
```

- [ ] **Step 8: Run migration and tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter="PingTest|ConnectionTest"`
Expected: All 5 tests PASS

- [ ] **Step 9: Commit**

```bash
git add database/migrations/*pings* database/migrations/*connections* app/Models/Ping.php app/Models/Connection.php database/factories/PingFactory.php database/factories/ConnectionFactory.php tests/Feature/Models/PingTest.php tests/Feature/Models/ConnectionTest.php
git commit -m "feat: add Ping and Connection models with expiry, scopes, and tests"
```

---

## Task 7: Message Model

**Files:**
- Create: `database/migrations/xxxx_create_messages_table.php`
- Create: `app/Models/Message.php`
- Create: `database/factories/MessageFactory.php`
- Create: `tests/Feature/Models/MessageTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Models/MessageTest.php
<?php

use App\Models\Connection;
use App\Models\Message;
use App\Models\User;

it('belongs to a connection and sender', function () {
    $sender = User::factory()->create();
    $connection = Connection::factory()->create(['user_a_id' => $sender->id]);

    $message = Message::factory()->create([
        'connection_id' => $connection->id,
        'sender_id' => $sender->id,
    ]);

    expect($message->connection->id)->toBe($connection->id)
        ->and($message->sender->id)->toBe($sender->id);
});

it('has a max length of 500 characters', function () {
    $message = Message::factory()->create(['body' => str_repeat('a', 500)]);

    expect(strlen($message->body))->toBe(500);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=MessageTest`
Expected: FAIL

- [ ] **Step 3: Create migration**

Run: `php artisan make:migration create_messages_table --no-interaction`

```php
Schema::create('messages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('connection_id')->constrained()->cascadeOnDelete();
    $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
    $table->string('body', 500);
    $table->timestamps();
});
```

- [ ] **Step 4: Create model and factory**

```php
// app/Models/Message.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(Connection::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
```

```php
// database/factories/MessageFactory.php
<?php

namespace Database\Factories;

use App\Models\Connection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'connection_id' => Connection::factory(),
            'sender_id' => User::factory(),
            'body' => $this->faker->sentence(),
        ];
    }
}
```

- [ ] **Step 5: Add messages relationship to Connection model**

```php
// Add to app/Models/Connection.php
public function messages(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(Message::class);
}
```

- [ ] **Step 6: Run migration and tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=MessageTest`
Expected: All 2 tests PASS

- [ ] **Step 7: Commit**

```bash
git add database/migrations/*messages* app/Models/Message.php database/factories/MessageFactory.php tests/Feature/Models/MessageTest.php app/Models/Connection.php
git commit -m "feat: add Message model for connection chat"
```

---

## Task 8: Session Check-In, Reactions, Q&A Models

**Files:**
- Create: `database/migrations/xxxx_create_session_check_ins_table.php`
- Create: `database/migrations/xxxx_create_session_reactions_table.php`
- Create: `database/migrations/xxxx_create_session_questions_table.php`
- Create: `database/migrations/xxxx_create_session_question_votes_table.php`
- Create: `app/Models/SessionCheckIn.php`
- Create: `app/Models/SessionReaction.php`
- Create: `app/Models/SessionQuestion.php`
- Create: `app/Models/SessionQuestionVote.php`
- Create: `database/factories/SessionCheckInFactory.php`
- Create: `database/factories/SessionReactionFactory.php`
- Create: `database/factories/SessionQuestionFactory.php`
- Create: `tests/Feature/Models/SessionEngagementTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Models/SessionEngagementTest.php
<?php

use App\Models\EventSession;
use App\Models\SessionCheckIn;
use App\Models\SessionQuestion;
use App\Models\SessionQuestionVote;
use App\Models\SessionReaction;
use App\Models\User;

it('tracks session check-ins', function () {
    $user = User::factory()->create();
    $session = EventSession::factory()->create();

    $checkIn = SessionCheckIn::factory()->create([
        'user_id' => $user->id,
        'event_session_id' => $session->id,
    ]);

    expect($checkIn->user->id)->toBe($user->id)
        ->and($checkIn->eventSession->id)->toBe($session->id)
        ->and($checkIn->checked_out_at)->toBeNull();
});

it('tracks session reactions', function () {
    $reaction = SessionReaction::factory()->create(['type' => 'lightbulb']);

    expect($reaction->type)->toBe('lightbulb')
        ->and($reaction->user)->toBeInstanceOf(User::class);
});

it('tracks session questions with votes', function () {
    $question = SessionQuestion::factory()->create(['body' => 'How does zero trust work at scale?']);
    $voter = User::factory()->create();

    SessionQuestionVote::create([
        'session_question_id' => $question->id,
        'user_id' => $voter->id,
    ]);

    expect($question->votes)->toHaveCount(1)
        ->and($question->body)->toBe('How does zero trust work at scale?');
});

it('can mark a question as answered', function () {
    $question = SessionQuestion::factory()->create(['is_answered' => true]);

    expect($question->is_answered)->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=SessionEngagementTest`
Expected: FAIL

- [ ] **Step 3: Create all four migrations**

Run:
```bash
php artisan make:migration create_session_check_ins_table --no-interaction
php artisan make:migration create_session_reactions_table --no-interaction
php artisan make:migration create_session_questions_table --no-interaction
php artisan make:migration create_session_question_votes_table --no-interaction
```

session_check_ins:
```php
Schema::create('session_check_ins', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('event_session_id')->constrained()->cascadeOnDelete();
    $table->timestamp('checked_out_at')->nullable();
    $table->timestamps();

    $table->unique(['user_id', 'event_session_id']);
});
```

session_reactions:
```php
Schema::create('session_reactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('event_session_id')->constrained()->cascadeOnDelete();
    $table->enum('type', ['lightbulb', 'clap', 'question', 'fire', 'think']);
    $table->timestamps();
});
```

session_questions:
```php
Schema::create('session_questions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('event_session_id')->constrained()->cascadeOnDelete();
    $table->string('body', 500);
    $table->boolean('is_answered')->default(false);
    $table->timestamps();
});
```

session_question_votes:
```php
Schema::create('session_question_votes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('session_question_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->timestamps();

    $table->unique(['session_question_id', 'user_id']);
});
```

- [ ] **Step 4: Create all four models**

```php
// app/Models/SessionCheckIn.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionCheckIn extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['checked_out_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function eventSession(): BelongsTo
    {
        return $this->belongsTo(EventSession::class);
    }
}
```

```php
// app/Models/SessionReaction.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionReaction extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function eventSession(): BelongsTo
    {
        return $this->belongsTo(EventSession::class);
    }
}
```

```php
// app/Models/SessionQuestion.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessionQuestion extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_answered' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function eventSession(): BelongsTo
    {
        return $this->belongsTo(EventSession::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(SessionQuestionVote::class);
    }
}
```

```php
// app/Models/SessionQuestionVote.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionQuestionVote extends Model
{
    protected $guarded = [];

    public function sessionQuestion(): BelongsTo
    {
        return $this->belongsTo(SessionQuestion::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 5: Create factories**

```php
// database/factories/SessionCheckInFactory.php
<?php

namespace Database\Factories;

use App\Models\EventSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SessionCheckInFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event_session_id' => EventSession::factory(),
        ];
    }
}
```

```php
// database/factories/SessionReactionFactory.php
<?php

namespace Database\Factories;

use App\Models\EventSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SessionReactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event_session_id' => EventSession::factory(),
            'type' => $this->faker->randomElement(['lightbulb', 'clap', 'question', 'fire', 'think']),
        ];
    }
}
```

```php
// database/factories/SessionQuestionFactory.php
<?php

namespace Database\Factories;

use App\Models\EventSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SessionQuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event_session_id' => EventSession::factory(),
            'body' => $this->faker->sentence() . '?',
            'is_answered' => false,
        ];
    }
}
```

- [ ] **Step 6: Add relationships to EventSession model**

```php
// Add to app/Models/EventSession.php
use Illuminate\Database\Eloquent\Relations\HasMany;

public function checkIns(): HasMany
{
    return $this->hasMany(SessionCheckIn::class);
}

public function reactions(): HasMany
{
    return $this->hasMany(SessionReaction::class);
}

public function questions(): HasMany
{
    return $this->hasMany(SessionQuestion::class);
}
```

- [ ] **Step 7: Run migration and tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=SessionEngagementTest`
Expected: All 4 tests PASS

- [ ] **Step 8: Commit**

```bash
git add database/migrations/*session_check_ins* database/migrations/*session_reactions* database/migrations/*session_questions* database/migrations/*session_question_votes* app/Models/SessionCheckIn.php app/Models/SessionReaction.php app/Models/SessionQuestion.php app/Models/SessionQuestionVote.php database/factories/SessionCheckInFactory.php database/factories/SessionReactionFactory.php database/factories/SessionQuestionFactory.php tests/Feature/Models/SessionEngagementTest.php app/Models/EventSession.php
git commit -m "feat: add session engagement models (check-in, reactions, Q&A)"
```

---

## Task 9: Booth Visit & Contact Card Models

**Files:**
- Create: `database/migrations/xxxx_create_booth_visits_table.php`
- Create: `database/migrations/xxxx_create_contact_cards_table.php`
- Create: `app/Models/BoothVisit.php`
- Create: `app/Models/ContactCard.php`
- Create: `database/factories/BoothVisitFactory.php`
- Create: `database/factories/ContactCardFactory.php`
- Create: `tests/Feature/Models/BoothVisitTest.php`
- Create: `tests/Feature/Models/ContactCardTest.php`

- [ ] **Step 1: Write tests**

```php
// tests/Feature/Models/BoothVisitTest.php
<?php

use App\Models\Booth;
use App\Models\BoothVisit;
use App\Models\User;

it('tracks booth visits with duration', function () {
    $visit = BoothVisit::factory()->create();

    expect($visit->user)->toBeInstanceOf(User::class)
        ->and($visit->booth)->toBeInstanceOf(Booth::class)
        ->and($visit->is_anonymous)->toBeFalse();
});

it('supports anonymous browsing', function () {
    $visit = BoothVisit::factory()->create(['is_anonymous' => true]);

    expect($visit->is_anonymous)->toBeTrue();
});

it('calculates visit duration', function () {
    $visit = BoothVisit::factory()->create([
        'entered_at' => now()->subMinutes(5),
        'left_at' => now(),
    ]);

    expect($visit->durationInMinutes())->toBe(5);
});
```

```php
// tests/Feature/Models/ContactCardTest.php
<?php

use App\Models\Connection;
use App\Models\ContactCard;
use App\Models\User;

it('stores contact card data for a connection', function () {
    $user = User::factory()->create();
    $connection = Connection::factory()->create(['user_a_id' => $user->id]);

    $card = ContactCard::factory()->create([
        'user_id' => $user->id,
        'connection_id' => $connection->id,
    ]);

    expect($card->user->id)->toBe($user->id)
        ->and($card->connection->id)->toBe($connection->id);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter="BoothVisitTest|ContactCardTest"`
Expected: FAIL

- [ ] **Step 3: Create booth_visits migration**

Run: `php artisan make:migration create_booth_visits_table --no-interaction`

```php
Schema::create('booth_visits', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('booth_id')->constrained()->cascadeOnDelete();
    $table->boolean('is_anonymous')->default(false);
    $table->string('participant_type')->nullable();
    $table->foreignId('from_session_id')->nullable()->constrained('event_sessions')->nullOnDelete();
    $table->timestamp('entered_at');
    $table->timestamp('left_at')->nullable();
    $table->timestamps();
});
```

- [ ] **Step 4: Create contact_cards migration**

Run: `php artisan make:migration create_contact_cards_table --no-interaction`

```php
Schema::create('contact_cards', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('connection_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->string('email');
    $table->string('company')->nullable();
    $table->string('role_title')->nullable();
    $table->string('phone')->nullable();
    $table->string('linkedin_url')->nullable();
    $table->timestamps();

    $table->unique(['user_id', 'connection_id']);
});
```

- [ ] **Step 5: Create models and factories**

```php
// app/Models/BoothVisit.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoothVisit extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_anonymous' => 'boolean',
            'entered_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booth(): BelongsTo
    {
        return $this->belongsTo(Booth::class);
    }

    public function fromSession(): BelongsTo
    {
        return $this->belongsTo(EventSession::class, 'from_session_id');
    }

    public function durationInMinutes(): int
    {
        $end = $this->left_at ?? now();

        return (int) $this->entered_at->diffInMinutes($end);
    }
}
```

```php
// app/Models/ContactCard.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactCard extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(Connection::class);
    }
}
```

```php
// database/factories/BoothVisitFactory.php
<?php

namespace Database\Factories;

use App\Models\Booth;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BoothVisitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'booth_id' => Booth::factory(),
            'is_anonymous' => false,
            'participant_type' => $this->faker->randomElement(['physical', 'remote']),
            'entered_at' => now(),
        ];
    }
}
```

```php
// database/factories/ContactCardFactory.php
<?php

namespace Database\Factories;

use App\Models\Connection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactCardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'connection_id' => Connection::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'company' => $this->faker->company(),
            'role_title' => $this->faker->jobTitle(),
        ];
    }
}
```

- [ ] **Step 6: Add visits relationship to Booth model**

```php
// Add to app/Models/Booth.php
public function visits(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(BoothVisit::class);
}
```

- [ ] **Step 7: Run migration and tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter="BoothVisitTest|ContactCardTest"`
Expected: All 4 tests PASS

- [ ] **Step 8: Commit**

```bash
git add database/migrations/*booth_visits* database/migrations/*contact_cards* app/Models/BoothVisit.php app/Models/ContactCard.php database/factories/BoothVisitFactory.php database/factories/ContactCardFactory.php tests/Feature/Models/BoothVisitTest.php tests/Feature/Models/ContactCardTest.php app/Models/Booth.php
git commit -m "feat: add BoothVisit and ContactCard models"
```

---

## Task 10: Icebreaker Questions & Magic Link Models

**Files:**
- Create: `database/migrations/xxxx_create_icebreaker_questions_table.php`
- Create: `database/migrations/xxxx_create_magic_links_table.php`
- Create: `app/Models/IcebreakerQuestion.php`
- Create: `app/Models/MagicLink.php`
- Create: `database/factories/IcebreakerQuestionFactory.php`
- Create: `database/factories/MagicLinkFactory.php`
- Create: `tests/Feature/Models/IcebreakerQuestionTest.php`
- Create: `tests/Feature/Models/MagicLinkTest.php`

- [ ] **Step 1: Write tests**

```php
// tests/Feature/Models/IcebreakerQuestionTest.php
<?php

use App\Models\Event;
use App\Models\IcebreakerQuestion;

it('belongs to an event', function () {
    $event = Event::factory()->create();
    $question = IcebreakerQuestion::factory()->create(['event_id' => $event->id]);

    expect($question->event->id)->toBe($event->id)
        ->and($question->question)->toBeString();
});
```

```php
// tests/Feature/Models/MagicLinkTest.php
<?php

use App\Models\Event;
use App\Models\MagicLink;
use App\Models\User;

it('generates a magic link with hashed token', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    $result = MagicLink::generate($user, $event);

    expect($result['token'])->toBeString()
        ->and(strlen($result['token']))->toBe(64)
        ->and($result['link']->token_hash)->toBe(hash('sha256', $result['token']))
        ->and($result['link']->user)->toBeInstanceOf(User::class)
        ->and($result['link']->event)->toBeInstanceOf(Event::class)
        ->and($result['link']->purpose)->toBe('login');
});

it('finds a link by raw token', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    $result = MagicLink::generate($user, $event);
    $found = MagicLink::findByToken($result['token']);

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($result['link']->id);
});

it('revokes older unused links on generation', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    $first = MagicLink::generate($user, $event);
    $second = MagicLink::generate($user, $event);

    expect($first['link']->fresh()->isRevoked())->toBeTrue()
        ->and($second['link']->fresh()->isRevoked())->toBeFalse();
});

it('knows if it is expired', function () {
    $valid = MagicLink::factory()->create(['expires_at' => now()->addDay()]);
    $expired = MagicLink::factory()->create(['expires_at' => now()->subHour()]);

    expect($valid->isExpired())->toBeFalse()
        ->and($expired->isExpired())->toBeTrue();
});

it('knows if it has been used', function () {
    $unused = MagicLink::factory()->create();
    $used = MagicLink::factory()->create(['used_at' => now()]);

    expect($unused->isUsed())->toBeFalse()
        ->and($used->isUsed())->toBeTrue();
});

it('can be consumed', function () {
    $link = MagicLink::factory()->create();

    $link->consume();

    expect($link->fresh()->used_at)->not->toBeNull();
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter="IcebreakerQuestionTest|MagicLinkTest"`
Expected: FAIL

- [ ] **Step 3: Create icebreaker_questions migration**

Run: `php artisan make:migration create_icebreaker_questions_table --no-interaction`

```php
Schema::create('icebreaker_questions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('event_id')->constrained()->cascadeOnDelete();
    $table->string('question');
    $table->timestamps();
});
```

- [ ] **Step 4: Create magic_links migration**

Run: `php artisan make:migration create_magic_links_table --no-interaction`

```php
Schema::create('magic_links', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('event_id')->constrained()->cascadeOnDelete();
    $table->string('token_hash', 64)->unique();
    $table->string('purpose')->default('login');
    $table->timestamp('expires_at');
    $table->timestamp('used_at')->nullable();
    $table->timestamp('revoked_at')->nullable();
    $table->timestamps();

    $table->index(['email', 'purpose', 'expires_at']);
});
```

- [ ] **Step 5: Create models and factories**

```php
// app/Models/IcebreakerQuestion.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcebreakerQuestion extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
```

```php
// app/Models/MagicLink.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MagicLink extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * Generate a raw token and store its SHA-256 hash.
     * Returns the raw token (only available at creation time).
     */
    public static function generate(User $user, Event $event, string $purpose = 'login'): array
    {
        $rawToken = Str::random(64);

        // Revoke any existing unused links for this user/event/purpose
        static::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        $link = static::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'token_hash' => hash('sha256', $rawToken),
            'purpose' => $purpose,
            'expires_at' => $event->ends_at->addDay(),
        ]);

        return ['link' => $link, 'token' => $rawToken];
    }

    public static function findByToken(string $rawToken): ?static
    {
        return static::where('token_hash', hash('sha256', $rawToken))->first();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->isUsed() && ! $this->isRevoked();
    }

    public function consume(): void
    {
        $this->update(['used_at' => now()]);
    }
}
```

```php
// database/factories/IcebreakerQuestionFactory.php
<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class IcebreakerQuestionFactory extends Factory
{
    private static array $questions = [
        "What's the boldest tech bet you've made this year?",
        "What brought you to this event?",
        "What's one thing you hope to learn today?",
        "What's the most underrated technology right now?",
        "If you could solve one problem in your industry, what would it be?",
    ];

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'question' => $this->faker->randomElement(self::$questions),
        ];
    }
}
```

```php
// database/factories/MagicLinkFactory.php
<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MagicLinkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event_id' => Event::factory(),
            'token_hash' => hash('sha256', Str::random(64)),
            'purpose' => 'login',
            'expires_at' => now()->addDays(2),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subHour(),
        ]);
    }
}
```

- [ ] **Step 6: Add relationships to Event model**

```php
// Add to app/Models/Event.php
public function icebreakerQuestions(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(IcebreakerQuestion::class);
}

public function magicLinks(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(MagicLink::class);
}

public function participants(): BelongsToMany
{
    return $this->belongsToMany(User::class)
        ->withPivot([
            'participant_type', 'status', 'context_badge',
            'icebreaker_answer', 'open_to_call',
            'available_after_session', 'last_active_at',
        ])
        ->withTimestamps();
}
```

- [ ] **Step 7: Run migration and tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter="IcebreakerQuestionTest|MagicLinkTest"`
Expected: All 5 tests PASS

- [ ] **Step 8: Commit**

```bash
git add database/migrations/*icebreaker* database/migrations/*magic_links* app/Models/IcebreakerQuestion.php app/Models/MagicLink.php database/factories/IcebreakerQuestionFactory.php database/factories/MagicLinkFactory.php tests/Feature/Models/IcebreakerQuestionTest.php tests/Feature/Models/MagicLinkTest.php app/Models/Event.php
git commit -m "feat: add IcebreakerQuestion and MagicLink models"
```

---

## Task 11: Magic Link Authentication Flow

**Files:**
- Create: `app/Actions/CreateMagicLink.php`
- Create: `app/Actions/AuthenticateViaMagicLink.php`
- Create: `app/Http/Controllers/Auth/MagicLinkController.php`
- Create: `app/Notifications/MagicLinkNotification.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Auth/MagicLinkAuthTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Auth/MagicLinkAuthTest.php
<?php

use App\Models\Event;
use App\Models\MagicLink;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Support\Facades\Notification;

it('sends a magic link email', function () {
    Notification::fake();

    $event = Event::factory()->create(['allow_open_registration' => true]);
    $user = User::factory()->create();
    $event->participants()->attach($user, ['participant_type' => 'physical', 'status' => 'available']);

    $response = $this->post(route('magic-link.send'), [
        'email' => $user->email,
        'event_slug' => $event->slug,
    ]);

    $response->assertOk();
    Notification::assertSentTo($user, MagicLinkNotification::class);
});

it('authenticates via a valid magic link', function () {
    $link = MagicLink::factory()->create();

    $response = $this->get(route('magic-link.authenticate', ['token' => $link->token]));

    $response->assertRedirect();
    $this->assertAuthenticatedAs($link->user);
    expect($link->fresh()->used_at)->not->toBeNull();
});

it('rejects an expired magic link', function () {
    $link = MagicLink::factory()->expired()->create();

    $response = $this->get(route('magic-link.authenticate', ['token' => $link->token]));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

it('rejects an already-used magic link', function () {
    $link = MagicLink::factory()->create(['used_at' => now()]);

    $response = $this->get(route('magic-link.authenticate', ['token' => $link->token]));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

it('creates a new user for open registration events', function () {
    Notification::fake();

    $event = Event::factory()->create(['allow_open_registration' => true]);

    $response = $this->post(route('magic-link.send'), [
        'email' => 'new@example.com',
        'event_slug' => $event->slug,
        'name' => 'New Participant',
    ]);

    $response->assertOk();

    $user = User::where('email', 'new@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('New Participant');

    Notification::assertSentTo($user, MagicLinkNotification::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=MagicLinkAuthTest`
Expected: FAIL

- [ ] **Step 3: Create the notification**

Run: `php artisan make:notification MagicLinkNotification --no-interaction`

```php
// app/Notifications/MagicLinkNotification.php
<?php

namespace App\Notifications;

use App\Models\MagicLink;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MagicLinkNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $rawToken,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('magic-link.authenticate', ['token' => $this->rawToken]);

        return (new MailMessage)
            ->subject('Your event access link')
            ->line('Click the button below to join the event.')
            ->action('Join Event', $url)
            ->line('This link expires in 48 hours and can only be used once.');
    }
}
```

- [ ] **Step 4: Create the CreateMagicLink action**

```php
// app/Actions/CreateMagicLink.php
<?php

namespace App\Actions;

use App\Models\Event;
use App\Models\MagicLink;
use App\Models\User;
use App\Notifications\MagicLinkNotification;

class CreateMagicLink
{
    public function handle(User $user, Event $event): array
    {
        $result = MagicLink::generate($user, $event);

        $user->notify(new MagicLinkNotification($result['token']));

        return $result;
    }
}
```

- [ ] **Step 5: Create the AuthenticateViaMagicLink action**

```php
// app/Actions/AuthenticateViaMagicLink.php
<?php

namespace App\Actions;

use App\Models\MagicLink;
use Illuminate\Support\Facades\Auth;

class AuthenticateViaMagicLink
{
    public function handle(string $token): ?MagicLink
    {
        $link = MagicLink::findByToken($token);

        if (! $link || ! $link->isValid()) {
            return null;
        }

        $link->consume();
        Auth::login($link->user, remember: true);

        return $link;
    }
}
```

- [ ] **Step 6: Create the controller**

Run: `php artisan make:controller Auth/MagicLinkController --no-interaction`

```php
// app/Http/Controllers/Auth/MagicLinkController.php
<?php

namespace App\Http\Controllers\Auth;

use App\Actions\AuthenticateViaMagicLink;
use App\Actions\CreateMagicLink;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;

class MagicLinkController extends Controller
{
    public function send(Request $request, CreateMagicLink $createMagicLink)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'event_slug' => ['required', 'string'],
            'name' => ['sometimes', 'string', 'max:255'],
        ]);

        $event = Event::where('slug', $validated['event_slug'])->firstOrFail();
        $user = User::where('email', $validated['email'])->first();

        if (! $user && $event->allow_open_registration) {
            $user = User::create([
                'name' => $validated['name'] ?? explode('@', $validated['email'])[0],
                'email' => $validated['email'],
                'password' => bcrypt(str()->random(32)),
            ]);

            $event->participants()->attach($user, [
                'participant_type' => 'remote',
                'status' => 'available',
            ]);
        }

        if (! $user) {
            // Don't reveal whether the email exists
            return response()->json(['message' => 'If your email is registered, you will receive a link.']);
        }

        $createMagicLink->handle($user, $event);

        return response()->json(['message' => 'If your email is registered, you will receive a link.']);
    }

    public function authenticate(string $token, AuthenticateViaMagicLink $authenticate)
    {
        $link = $authenticate->handle($token);

        if (! $link) {
            return redirect()->route('login')
                ->with('error', 'This link is invalid or has expired. Please request a new one.');
        }

        return redirect()->route('dashboard');
    }
}
```

- [ ] **Step 7: Add routes**

Add to `routes/web.php`:

```php
use App\Http\Controllers\Auth\MagicLinkController;

Route::post('/magic-link', [MagicLinkController::class, 'send'])->name('magic-link.send');
Route::get('/magic-link/{token}', [MagicLinkController::class, 'authenticate'])->name('magic-link.authenticate');
```

- [ ] **Step 8: Run migration and tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=MagicLinkAuthTest`
Expected: All 5 tests PASS

- [ ] **Step 9: Commit**

```bash
git add app/Actions/CreateMagicLink.php app/Actions/AuthenticateViaMagicLink.php app/Http/Controllers/Auth/MagicLinkController.php app/Notifications/MagicLinkNotification.php routes/web.php tests/Feature/Auth/MagicLinkAuthTest.php
git commit -m "feat: add magic link authentication flow"
```

- [ ] **Step 10: Disable unused Fortify features**

Edit `config/fortify.php` — comment out or remove these from the `features` array:

```php
'features' => [
    // Features::registration(),     // Disabled — using magic links
    // Features::resetPasswords(),   // Disabled — no passwords
    // Features::emailVerification(), // Disabled — magic link = verified
    // Features::updateProfileInformation(),
    // Features::updatePasswords(),
    // Features::twoFactorAuthentication(), // Disabled — no passwords
],
```

- [ ] **Step 11: Commit Fortify cleanup**

```bash
git add config/fortify.php
git commit -m "chore: disable unused Fortify features (registration, passwords, 2FA)"
```

---

## Task 12: Demo Event Seeder

**Files:**
- Create: `database/seeders/DemoEventSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

- [ ] **Step 1: Create the seeder**

Run: `php artisan make:seeder DemoEventSeeder --no-interaction`

```php
// database/seeders/DemoEventSeeder.php
<?php

namespace Database\Seeders;

use App\Models\Booth;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\IcebreakerQuestion;
use App\Models\InterestTag;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoEventSeeder extends Seeder
{
    public function run(): void
    {
        $organizer = User::factory()->organizer()->create([
            'name' => 'Event Organizer',
            'email' => 'organizer@demo.test',
        ]);

        $event = Event::factory()->live()->create([
            'organizer_id' => $organizer->id,
            'name' => 'BSI Cyber Security Conference 2026',
            'description' => 'The premier hybrid cybersecurity event bridging physical and remote participants.',
            'venue' => 'Congress Center Basel',
            'allow_open_registration' => true,
        ]);

        // Interest tags
        $tagNames = [
            'Zero Trust', 'Cloud Migration', 'DevOps', 'AI/ML', 'Startup',
            'Enterprise', 'Cybersecurity', 'Data Privacy', 'IoT', 'Blockchain',
            'Remote Work', 'Leadership', 'Open Source', 'Edge Computing', 'API Design',
            'Platform Engineering', 'Observability', 'FinTech', 'HealthTech', 'GreenTech',
        ];

        $tags = collect($tagNames)->map(fn (string $name) =>
            InterestTag::create(['name' => $name])
        );

        $event->interestTags()->attach($tags->pluck('id'));

        // Icebreaker questions
        $icebreakers = [
            "What's the boldest tech bet you've made this year?",
            "What brought you to this event?",
            "What's one thing you hope to learn today?",
            "What's the most underrated technology right now?",
            "If you could solve one problem in your industry, what would it be?",
        ];

        foreach ($icebreakers as $question) {
            IcebreakerQuestion::create(['event_id' => $event->id, 'question' => $question]);
        }

        // Sessions
        $sessions = [
            ['title' => 'Keynote — Zero Trust Architecture in 2026', 'speaker' => 'Dr. Sarah Chen', 'room' => 'Main Stage', 'offset' => 0],
            ['title' => 'Workshop: Cloud Migration Strategies', 'speaker' => 'Marcus Weber', 'room' => 'Room A', 'offset' => 60],
            ['title' => 'Panel: AI in Cybersecurity', 'speaker' => 'Various', 'room' => 'Room B', 'offset' => 60],
            ['title' => 'Talk: DevOps Security Best Practices', 'speaker' => 'Lena Fischer', 'room' => 'Room A', 'offset' => 120],
            ['title' => 'Fireside Chat: The Future of Data Privacy', 'speaker' => 'Prof. Alex Müller', 'room' => 'Main Stage', 'offset' => 180],
        ];

        foreach ($sessions as $session) {
            EventSession::create([
                'event_id' => $event->id,
                'title' => $session['title'],
                'description' => "An engaging session about {$session['title']}.",
                'speaker' => $session['speaker'],
                'room' => $session['room'],
                'starts_at' => $event->starts_at->addMinutes($session['offset']),
                'ends_at' => $event->starts_at->addMinutes($session['offset'] + 45),
            ]);
        }

        // Booths
        $boothData = [
            ['name' => 'CyberDefense AG', 'tags' => ['Zero Trust', 'Cybersecurity']],
            ['name' => 'CloudScale Solutions', 'tags' => ['Cloud Migration', 'DevOps']],
            ['name' => 'AI Security Labs', 'tags' => ['AI/ML', 'Cybersecurity']],
            ['name' => 'PrivacyFirst GmbH', 'tags' => ['Data Privacy', 'Enterprise']],
        ];

        foreach ($boothData as $data) {
            $booth = Booth::create([
                'event_id' => $event->id,
                'name' => $data['name'] . ' Booth',
                'company' => $data['name'],
                'description' => "Visit {$data['name']} to learn about our solutions.",
                'content_links' => [['label' => 'Website', 'url' => 'https://example.com']],
            ]);

            $boothTagIds = $tags->filter(fn ($t) => in_array($t->name, $data['tags']))->pluck('id');
            $booth->interestTags()->attach($boothTagIds);

            // Create booth staff
            $staff = User::factory()->create(['name' => "{$data['name']} Staff"]);
            $booth->staff()->attach($staff);
            $event->participants()->attach($staff, ['participant_type' => 'physical', 'status' => 'available']);
        }

        // Create demo participants
        $physicalParticipants = User::factory(15)->withProfile()->create();
        $remoteParticipants = User::factory(10)->withProfile()->create();

        foreach ($physicalParticipants as $participant) {
            $event->participants()->attach($participant, ['participant_type' => 'physical', 'status' => 'available']);
            $participant->interestTags()->attach(
                $tags->random(3)->pluck('id'),
                ['event_id' => $event->id]
            );
        }

        foreach ($remoteParticipants as $participant) {
            $event->participants()->attach($participant, ['participant_type' => 'remote', 'status' => 'available']);
            $participant->interestTags()->attach(
                $tags->random(3)->pluck('id'),
                ['event_id' => $event->id]
            );
        }
    }
}
```

- [ ] **Step 2: Update DatabaseSeeder**

```php
// database/seeders/DatabaseSeeder.php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DemoEventSeeder::class);
    }
}
```

- [ ] **Step 3: Test the seeder**

Run: `php artisan migrate:fresh --seed --no-interaction`
Expected: No errors, seeder runs successfully

- [ ] **Step 4: Verify seeded data**

Run: `php artisan tinker --execute 'echo "Events: " . \App\Models\Event::count() . "\nTags: " . \App\Models\InterestTag::count() . "\nSessions: " . \App\Models\EventSession::count() . "\nBooths: " . \App\Models\Booth::count() . "\nUsers: " . \App\Models\User::count();'`

Expected output:
```
Events: 1
Tags: 20
Sessions: 5
Booths: 4
Users: 30
```

- [ ] **Step 5: Run all tests to make sure nothing is broken**

Run: `php artisan test --compact`
Expected: All tests PASS

- [ ] **Step 6: Commit**

```bash
git add database/seeders/DemoEventSeeder.php database/seeders/DatabaseSeeder.php
git commit -m "feat: add demo event seeder with realistic data"
```

---

## Task 13: Run Full Suite & Lint

- [ ] **Step 1: Run all tests**

Run: `php artisan test --compact`
Expected: All tests PASS

- [ ] **Step 2: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`
Expected: All files pass (or auto-fixed)

- [ ] **Step 3: Commit any formatting fixes**

```bash
git add -A
git commit -m "style: apply Pint formatting"
```
