# Plan 4: Interactions — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the ping flow, mutual match detection, instant chat with real-time messaging, and 3-minute video call setup. The core interaction pipeline: Ping → Match → Chat/Call.

**Architecture:** A `PingService` handles sending pings with rate limiting, block checking, and mutual match detection. Mutual match detection uses a DB transaction with `lockForUpdate()` to prevent race conditions where simultaneous pings create duplicate connections. Chat uses a `MessageController` with real-time broadcasting. Video calls use a `CallController` that generates a WebRTC signaling room ID — actual WebRTC peer connection is handled client-side. TURN/STUN server config via env vars for NAT traversal.

**Tech Stack:** Laravel 13, Reverb (WebSockets), WebRTC (client-side), Inertia v3, Vue 3, Pest v4

**Depends on:** Plan 1 (models), Plan 2 (presence/broadcasting), Plan 3 (suggestions)

## TDD Standard

- Start each task with failing tests before implementation.
- Cover negative and edge cases: self-pings, duplicates, cooldowns, rate limits, blocked users, unauthorized connection access, empty messages, and expired/denied call actions.
- For chat and interaction endpoints, add `assertInertia` or HTTP assertions for prop shape, unread state, and permission boundaries.
- Add browser smoke coverage plus real browser tests for one successful interaction flow and one failure-path flow.

---

## File Structure

### Services
```
app/Services/PingService.php
```

### Controllers
```
app/Http/Controllers/PingController.php
app/Http/Controllers/MessageController.php
app/Http/Controllers/CallController.php
```

### Events (broadcast)
```
app/Events/PingReceived.php
app/Events/MutualMatchCreated.php
app/Events/NewMessage.php
app/Events/CallInitiated.php
```

### Vue Pages & Components
```
resources/js/pages/Event/Chat.vue
resources/js/components/interaction/PingButton.vue
resources/js/components/interaction/MatchNotification.vue
resources/js/components/interaction/ChatWindow.vue
resources/js/components/interaction/VideoCall.vue
```

---

## Task 1: Ping Service

**Files:**
- Create: `app/Services/PingService.php`
- Create: `tests/Feature/Services/PingServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Services/PingServiceTest.php
<?php

use App\Models\Connection;
use App\Models\Event;
use App\Models\Ping;
use App\Models\User;
use App\Services\PingService;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->service = app(PingService::class);
});

it('creates a ping between two users', function () {
    $sender = User::factory()->create();
    $receiver = User::factory()->create();
    $this->event->participants()->attach($sender, ['participant_type' => 'physical', 'status' => 'available']);
    $this->event->participants()->attach($receiver, ['participant_type' => 'remote', 'status' => 'available']);

    $ping = $this->service->send($sender, $receiver, $this->event);

    expect($ping)->toBeInstanceOf(Ping::class)
        ->and($ping->sender_id)->toBe($sender->id)
        ->and($ping->receiver_id)->toBe($receiver->id)
        ->and($ping->status)->toBe('pending');
});

it('detects mutual match and creates connection', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $this->event->participants()->attach($userA, ['participant_type' => 'physical', 'status' => 'available']);
    $this->event->participants()->attach($userB, ['participant_type' => 'remote', 'status' => 'available']);

    // A pings B
    $this->service->send($userA, $userB, $this->event);

    // B pings A — should create mutual match
    $ping = $this->service->send($userB, $userA, $this->event);

    expect($ping->status)->toBe('matched');
    expect(Connection::where('event_id', $this->event->id)->count())->toBe(1);
});

it('marks cross-world connections', function () {
    $physical = User::factory()->create();
    $remote = User::factory()->create();
    $this->event->participants()->attach($physical, ['participant_type' => 'physical', 'status' => 'available']);
    $this->event->participants()->attach($remote, ['participant_type' => 'remote', 'status' => 'available']);

    $this->service->send($physical, $remote, $this->event);
    $this->service->send($remote, $physical, $this->event);

    $connection = Connection::first();
    expect($connection->is_cross_world)->toBeTrue();
});

it('rate limits to 10 pings per hour', function () {
    $sender = User::factory()->create();
    $this->event->participants()->attach($sender, ['participant_type' => 'physical', 'status' => 'available']);

    $receivers = User::factory(11)->create();
    foreach ($receivers as $receiver) {
        $this->event->participants()->attach($receiver, ['participant_type' => 'remote', 'status' => 'available']);
    }

    for ($i = 0; $i < 10; $i++) {
        $this->service->send($sender, $receivers[$i], $this->event);
    }

    $this->service->send($sender, $receivers[10], $this->event);
})->throws(\App\Exceptions\RateLimitExceededException::class);

it('prevents duplicate active pings', function () {
    $sender = User::factory()->create();
    $receiver = User::factory()->create();
    $this->event->participants()->attach($sender, ['participant_type' => 'physical', 'status' => 'available']);
    $this->event->participants()->attach($receiver, ['participant_type' => 'remote', 'status' => 'available']);

    $this->service->send($sender, $receiver, $this->event);
    $this->service->send($sender, $receiver, $this->event);
})->throws(\App\Exceptions\DuplicatePingException::class);

it('respects the 3-ignore cooldown', function () {
    $sender = User::factory()->create();
    $receiver = User::factory()->create();
    $this->event->participants()->attach($sender, ['participant_type' => 'physical', 'status' => 'available']);
    $this->event->participants()->attach($receiver, ['participant_type' => 'remote', 'status' => 'available']);

    // Create 3 ignored pings
    for ($i = 0; $i < 3; $i++) {
        Ping::factory()->create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'event_id' => $this->event->id,
            'status' => 'ignored',
        ]);
    }

    $this->service->send($sender, $receiver, $this->event);
})->throws(\App\Exceptions\CooldownException::class);
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=PingServiceTest`
Expected: FAIL

- [ ] **Step 3: Create exception classes**

```php
// app/Exceptions/RateLimitExceededException.php
<?php

namespace App\Exceptions;

use RuntimeException;

class RateLimitExceededException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('You have reached the maximum number of pings per hour.');
    }
}
```

```php
// app/Exceptions/DuplicatePingException.php
<?php

namespace App\Exceptions;

use RuntimeException;

class DuplicatePingException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('You have already pinged this person.');
    }
}
```

```php
// app/Exceptions/CooldownException.php
<?php

namespace App\Exceptions;

use RuntimeException;

class CooldownException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This person has not responded to your previous pings.');
    }
}
```

- [ ] **Step 4: Create PingService**

```php
// app/Services/PingService.php
<?php

namespace App\Services;

use App\Events\MutualMatchCreated;
use App\Events\PingReceived;
use App\Exceptions\CooldownException;
use App\Exceptions\DuplicatePingException;
use App\Exceptions\RateLimitExceededException;
use App\Models\Connection;
use App\Models\Event;
use App\Models\Ping;
use App\Models\User;

class PingService
{
    private const MAX_PINGS_PER_HOUR = 10;
    private const IGNORE_COOLDOWN = 3;

    public function send(User $sender, User $receiver, Event $event): Ping
    {
        $this->validateRateLimit($sender, $event);
        $this->validateNoDuplicate($sender, $receiver, $event);
        $this->validateCooldown($sender, $receiver, $event);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($sender, $receiver, $event) {
            $ping = Ping::create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'event_id' => $event->id,
                'status' => 'pending',
            ]);

            // Check for mutual match with lock to prevent race conditions
            $reciprocal = Ping::where('sender_id', $receiver->id)
                ->where('receiver_id', $sender->id)
                ->where('event_id', $event->id)
                ->where('status', 'pending')
                ->active()
                ->lockForUpdate()
                ->first();

            if ($reciprocal) {
                $ping->update(['status' => 'matched']);
                $reciprocal->update(['status' => 'matched']);

                $connection = $this->createConnection($sender, $receiver, $event);

                MutualMatchCreated::dispatch($event, $connection, $sender, $receiver);

                return $ping;
            }

            PingReceived::dispatch($event, $ping);

            return $ping;
        });
    }

    private function validateRateLimit(User $sender, Event $event): void
    {
        $recentCount = Ping::where('sender_id', $sender->id)
            ->where('event_id', $event->id)
            ->where('created_at', '>', now()->subHour())
            ->count();

        if ($recentCount >= self::MAX_PINGS_PER_HOUR) {
            throw new RateLimitExceededException();
        }
    }

    private function validateNoDuplicate(User $sender, User $receiver, Event $event): void
    {
        $exists = Ping::where('sender_id', $sender->id)
            ->where('receiver_id', $receiver->id)
            ->where('event_id', $event->id)
            ->active()
            ->exists();

        if ($exists) {
            throw new DuplicatePingException();
        }
    }

    private function validateCooldown(User $sender, User $receiver, Event $event): void
    {
        $ignoredCount = Ping::where('sender_id', $sender->id)
            ->where('receiver_id', $receiver->id)
            ->where('event_id', $event->id)
            ->where('status', 'ignored')
            ->count();

        if ($ignoredCount >= self::IGNORE_COOLDOWN) {
            throw new CooldownException();
        }
    }

    private function createConnection(User $a, User $b, Event $event): Connection
    {
        $pivotA = $a->events()->where('event_id', $event->id)->first()?->pivot;
        $pivotB = $b->events()->where('event_id', $event->id)->first()?->pivot;

        $isCrossWorld = $pivotA && $pivotB && $pivotA->participant_type !== $pivotB->participant_type;

        return Connection::create([
            'user_a_id' => $a->id,
            'user_b_id' => $b->id,
            'event_id' => $event->id,
            'context' => 'Mutual ping',
            'is_cross_world' => $isCrossWorld,
        ]);
    }
}
```

- [ ] **Step 5: Run tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=PingServiceTest`
Expected: All 6 tests PASS

- [ ] **Step 6: Commit**

```bash
git add app/Services/PingService.php app/Exceptions/RateLimitExceededException.php app/Exceptions/DuplicatePingException.php app/Exceptions/CooldownException.php tests/Feature/Services/PingServiceTest.php
git commit -m "feat: add PingService with rate limiting, duplicate check, cooldown, and mutual match"
```

---

## Task 2: Ping & Match Broadcast Events

**Files:**
- Create: `app/Events/PingReceived.php`
- Create: `app/Events/MutualMatchCreated.php`
- Create: `tests/Feature/Events/PingBroadcastTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Events/PingBroadcastTest.php
<?php

use App\Events\MutualMatchCreated;
use App\Events\PingReceived;
use App\Models\Connection;
use App\Models\Event;
use App\Models\Ping;
use App\Models\User;

it('broadcasts ping on receiver private channel', function () {
    $event = Event::factory()->live()->create();
    $ping = Ping::factory()->create(['event_id' => $event->id]);

    $broadcastEvent = new PingReceived($event, $ping);

    expect($broadcastEvent->broadcastOn()->name)->toBe("private-user.{$ping->receiver_id}.notifications");
});

it('broadcasts mutual match to both users', function () {
    $event = Event::factory()->live()->create();
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $connection = Connection::factory()->create([
        'user_a_id' => $userA->id,
        'user_b_id' => $userB->id,
        'event_id' => $event->id,
    ]);

    $broadcastEvent = new MutualMatchCreated($event, $connection, $userA, $userB);
    $channels = $broadcastEvent->broadcastOn();

    expect($channels)->toHaveCount(2);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=PingBroadcastTest`
Expected: FAIL

- [ ] **Step 3: Create PingReceived event**

```php
// app/Events/PingReceived.php
<?php

namespace App\Events;

use App\Models\Event;
use App\Models\Ping;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PingReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Event $event,
        public Ping $ping,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("user.{$this->ping->receiver_id}.notifications");
    }

    public function broadcastWith(): array
    {
        return [
            'ping_id' => $this->ping->id,
            'sender' => [
                'id' => $this->ping->sender->id,
                'name' => $this->ping->sender->name,
            ],
            'event_id' => $this->event->id,
        ];
    }
}
```

- [ ] **Step 4: Create MutualMatchCreated event**

```php
// app/Events/MutualMatchCreated.php
<?php

namespace App\Events;

use App\Models\Connection;
use App\Models\Event;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MutualMatchCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Event $event,
        public Connection $connection,
        public User $userA,
        public User $userB,
    ) {}

    /** @return array<PrivateChannel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->userA->id}.notifications"),
            new PrivateChannel("user.{$this->userB->id}.notifications"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'connection_id' => $this->connection->id,
            'user_a' => ['id' => $this->userA->id, 'name' => $this->userA->name],
            'user_b' => ['id' => $this->userB->id, 'name' => $this->userB->name],
            'event_id' => $this->event->id,
        ];
    }
}
```

- [ ] **Step 5: Add channel authorization**

Add to `routes/channels.php`:

```php
Broadcast::channel('user.{userId}.notifications', function ($user, int $userId) {
    return $user->id === $userId;
});
```

- [ ] **Step 6: Run tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=PingBroadcastTest`
Expected: All 2 tests PASS

- [ ] **Step 7: Commit**

```bash
git add app/Events/PingReceived.php app/Events/MutualMatchCreated.php routes/channels.php tests/Feature/Events/PingBroadcastTest.php
git commit -m "feat: add PingReceived and MutualMatchCreated broadcast events"
```

---

## Task 3: Ping Controller

**Files:**
- Create: `app/Http/Controllers/PingController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Http/PingControllerTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Http/PingControllerTest.php
<?php

use App\Models\Event;
use App\Models\Ping;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->sender = User::factory()->create();
    $this->receiver = User::factory()->create();
    $this->event->participants()->attach($this->sender, ['participant_type' => 'physical', 'status' => 'available']);
    $this->event->participants()->attach($this->receiver, ['participant_type' => 'remote', 'status' => 'available']);
});

it('sends a ping', function () {
    $response = $this->actingAs($this->sender)
        ->post(route('event.ping', [$this->event, $this->receiver]));

    $response->assertOk();
    expect(Ping::count())->toBe(1);
});

it('returns 429 when rate limited', function () {
    Ping::factory(10)->create([
        'sender_id' => $this->sender->id,
        'event_id' => $this->event->id,
        'created_at' => now(),
    ]);

    $response = $this->actingAs($this->sender)
        ->post(route('event.ping', [$this->event, $this->receiver]));

    $response->assertStatus(429);
});

it('ignores a ping', function () {
    $ping = Ping::factory()->create([
        'sender_id' => $this->receiver->id,
        'receiver_id' => $this->sender->id,
        'event_id' => $this->event->id,
    ]);

    $response = $this->actingAs($this->sender)
        ->patch(route('event.ping.ignore', [$this->event, $ping]));

    $response->assertOk();
    expect($ping->fresh()->status)->toBe('ignored');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=PingControllerTest`
Expected: FAIL

- [ ] **Step 3: Create PingController**

Run: `php artisan make:controller PingController --no-interaction`

```php
// app/Http/Controllers/PingController.php
<?php

namespace App\Http\Controllers;

use App\Exceptions\CooldownException;
use App\Exceptions\DuplicatePingException;
use App\Exceptions\RateLimitExceededException;
use App\Models\Event;
use App\Models\Ping;
use App\Models\User;
use App\Services\PingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PingController extends Controller
{
    public function store(Request $request, Event $event, User $user, PingService $pingService): JsonResponse
    {
        try {
            $ping = $pingService->send($request->user(), $user, $event);

            return response()->json([
                'message' => $ping->status === 'matched' ? "It's a match!" : 'Ping sent!',
                'status' => $ping->status,
            ]);
        } catch (RateLimitExceededException) {
            return response()->json(['message' => 'Too many pings. Try again later.'], 429);
        } catch (DuplicatePingException) {
            return response()->json(['message' => 'Already pinged this person.'], 409);
        } catch (CooldownException) {
            return response()->json(['message' => 'This person has not responded to your pings.'], 403);
        }
    }

    public function ignore(Request $request, Event $event, Ping $ping): JsonResponse
    {
        abort_unless($ping->receiver_id === $request->user()->id, 403);

        $ping->update(['status' => 'ignored']);

        return response()->json(['message' => 'Ping ignored']);
    }
}
```

- [ ] **Step 4: Add routes**

Add to `routes/web.php` inside the auth middleware group:

```php
use App\Http\Controllers\PingController;

Route::post('/event/{event:slug}/ping/{user}', [PingController::class, 'store'])->name('event.ping');
Route::patch('/event/{event:slug}/ping/{ping}/ignore', [PingController::class, 'ignore'])->name('event.ping.ignore');
```

- [ ] **Step 5: Run tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=PingControllerTest`
Expected: All 3 tests PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/PingController.php routes/web.php tests/Feature/Http/PingControllerTest.php
git commit -m "feat: add PingController with send, rate limit, and ignore endpoints"
```

---

## Task 4: Message Controller & Chat Broadcasting

**Files:**
- Create: `app/Http/Controllers/MessageController.php`
- Create: `app/Events/NewMessage.php`
- Modify: `routes/web.php`
- Modify: `routes/channels.php`
- Create: `tests/Feature/Http/MessageControllerTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Http/MessageControllerTest.php
<?php

use App\Models\Connection;
use App\Models\Event;
use App\Models\Message;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->userA = User::factory()->create();
    $this->userB = User::factory()->create();
    $this->connection = Connection::factory()->create([
        'user_a_id' => $this->userA->id,
        'user_b_id' => $this->userB->id,
        'event_id' => $this->event->id,
    ]);
});

it('sends a message in a connection', function () {
    $response = $this->actingAs($this->userA)
        ->post(route('connection.messages.store', $this->connection), [
            'body' => 'Hello, nice to meet you!',
        ]);

    $response->assertOk();
    expect(Message::count())->toBe(1);
});

it('rejects messages over 500 chars', function () {
    $response = $this->actingAs($this->userA)
        ->post(route('connection.messages.store', $this->connection), [
            'body' => str_repeat('a', 501),
        ]);

    $response->assertUnprocessable();
});

it('returns message history', function () {
    Message::factory(3)->create([
        'connection_id' => $this->connection->id,
        'sender_id' => $this->userA->id,
    ]);

    $response = $this->actingAs($this->userA)
        ->get(route('connection.messages.index', $this->connection));

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('prevents non-connection users from sending', function () {
    $stranger = User::factory()->create();

    $response = $this->actingAs($stranger)
        ->post(route('connection.messages.store', $this->connection), [
            'body' => 'Hello',
        ]);

    $response->assertForbidden();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=MessageControllerTest`
Expected: FAIL

- [ ] **Step 3: Create NewMessage broadcast event**

```php
// app/Events/NewMessage.php
<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("connection.{$this->message->connection_id}.chat");
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $this->message->sender->name,
            'body' => $this->message->body,
            'created_at' => $this->message->created_at->toISOString(),
        ];
    }
}
```

- [ ] **Step 4: Create MessageController**

Run: `php artisan make:controller MessageController --no-interaction`

```php
// app/Http/Controllers/MessageController.php
<?php

namespace App\Http\Controllers;

use App\Events\NewMessage;
use App\Models\Connection;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request, Connection $connection): JsonResponse
    {
        $this->authorizeConnection($request, $connection);

        $messages = $connection->messages()
            ->with('sender:id,name')
            ->orderBy('created_at')
            ->get()
            ->map(fn (Message $m) => [
                'id' => $m->id,
                'sender_id' => $m->sender_id,
                'sender_name' => $m->sender->name,
                'body' => $m->body,
                'created_at' => $m->created_at->toISOString(),
            ]);

        return response()->json(['data' => $messages]);
    }

    public function store(Request $request, Connection $connection): JsonResponse
    {
        $this->authorizeConnection($request, $connection);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:500'],
        ]);

        $message = Message::create([
            'connection_id' => $connection->id,
            'sender_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        NewMessage::dispatch($message);

        return response()->json(['message' => 'Sent']);
    }

    private function authorizeConnection(Request $request, Connection $connection): void
    {
        $userId = $request->user()->id;

        abort_unless(
            $connection->user_a_id === $userId || $connection->user_b_id === $userId,
            403
        );
    }
}
```

- [ ] **Step 5: Add routes and channel**

Add to `routes/web.php` inside the auth middleware group:

```php
use App\Http\Controllers\MessageController;

Route::get('/connections/{connection}/messages', [MessageController::class, 'index'])->name('connection.messages.index');
Route::post('/connections/{connection}/messages', [MessageController::class, 'store'])->name('connection.messages.store');
```

Add to `routes/channels.php`:

```php
Broadcast::channel('connection.{connectionId}.chat', function ($user, int $connectionId) {
    $connection = \App\Models\Connection::find($connectionId);
    return $connection && ($connection->user_a_id === $user->id || $connection->user_b_id === $user->id);
});
```

- [ ] **Step 6: Run tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=MessageControllerTest`
Expected: All 4 tests PASS

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/MessageController.php app/Events/NewMessage.php routes/web.php routes/channels.php tests/Feature/Http/MessageControllerTest.php
git commit -m "feat: add MessageController with chat broadcasting"
```

---

## Task 5: Call Controller (WebRTC Signaling)

**Files:**
- Create: `app/Http/Controllers/CallController.php`
- Create: `app/Events/CallInitiated.php`
- Create: `database/migrations/xxxx_create_calls_table.php`
- Create: `app/Models/Call.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Http/CallControllerTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Http/CallControllerTest.php
<?php

use App\Models\Call;
use App\Models\Connection;
use App\Models\Event;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->userA = User::factory()->create();
    $this->userB = User::factory()->create();
    $this->connection = Connection::factory()->create([
        'user_a_id' => $this->userA->id,
        'user_b_id' => $this->userB->id,
        'event_id' => $this->event->id,
    ]);
});

it('initiates a 3-minute call', function () {
    $response = $this->actingAs($this->userA)
        ->post(route('connection.call.start', $this->connection));

    $response->assertOk()
        ->assertJsonStructure(['call_id', 'room_id', 'expires_at']);
});

it('extends a call by 3 minutes', function () {
    $call = Call::create([
        'connection_id' => $this->connection->id,
        'initiator_id' => $this->userA->id,
        'room_id' => 'test-room',
        'started_at' => now(),
        'expires_at' => now()->addMinutes(3),
        'extensions' => 0,
    ]);

    $response = $this->actingAs($this->userA)
        ->patch(route('connection.call.extend', [$this->connection, $call]));

    $response->assertOk();
    expect($call->fresh()->extensions)->toBe(1);
});

it('prevents more than 2 extensions', function () {
    $call = Call::create([
        'connection_id' => $this->connection->id,
        'initiator_id' => $this->userA->id,
        'room_id' => 'test-room',
        'started_at' => now(),
        'expires_at' => now()->addMinutes(9),
        'extensions' => 2,
    ]);

    $response = $this->actingAs($this->userA)
        ->patch(route('connection.call.extend', [$this->connection, $call]));

    $response->assertStatus(422);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=CallControllerTest`
Expected: FAIL

- [ ] **Step 3: Create calls migration**

Run: `php artisan make:migration create_calls_table --no-interaction`

```php
Schema::create('calls', function (Blueprint $table) {
    $table->id();
    $table->foreignId('connection_id')->constrained()->cascadeOnDelete();
    $table->foreignId('initiator_id')->constrained('users')->cascadeOnDelete();
    $table->string('room_id')->unique();
    $table->timestamp('started_at');
    $table->timestamp('expires_at');
    $table->timestamp('ended_at')->nullable();
    $table->unsignedTinyInteger('extensions')->default(0);
    $table->timestamps();
});
```

- [ ] **Step 4: Create Call model**

```php
// app/Models/Call.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Call extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(Connection::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function canExtend(): bool
    {
        return $this->extensions < 2;
    }
}
```

- [ ] **Step 5: Create CallInitiated event**

```php
// app/Events/CallInitiated.php
<?php

namespace App\Events;

use App\Models\Call;
use App\Models\Connection;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallInitiated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Connection $connection,
        public Call $call,
        public int $receiverId,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("user.{$this->receiverId}.notifications");
    }

    public function broadcastWith(): array
    {
        return [
            'call_id' => $this->call->id,
            'room_id' => $this->call->room_id,
            'connection_id' => $this->connection->id,
            'initiator_name' => $this->call->initiator->name,
            'expires_at' => $this->call->expires_at->toISOString(),
        ];
    }
}
```

- [ ] **Step 6: Create CallController**

Run: `php artisan make:controller CallController --no-interaction`

```php
// app/Http/Controllers/CallController.php
<?php

namespace App\Http\Controllers;

use App\Events\CallInitiated;
use App\Models\Call;
use App\Models\Connection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CallController extends Controller
{
    public function start(Request $request, Connection $connection): JsonResponse
    {
        $userId = $request->user()->id;
        abort_unless($connection->user_a_id === $userId || $connection->user_b_id === $userId, 403);

        $receiverId = $connection->user_a_id === $userId
            ? $connection->user_b_id
            : $connection->user_a_id;

        $call = Call::create([
            'connection_id' => $connection->id,
            'initiator_id' => $userId,
            'room_id' => Str::uuid()->toString(),
            'started_at' => now(),
            'expires_at' => now()->addMinutes(3),
        ]);

        CallInitiated::dispatch($connection, $call, $receiverId);

        return response()->json([
            'call_id' => $call->id,
            'room_id' => $call->room_id,
            'expires_at' => $call->expires_at->toISOString(),
        ]);
    }

    public function extend(Request $request, Connection $connection, Call $call): JsonResponse
    {
        $userId = $request->user()->id;
        abort_unless($connection->user_a_id === $userId || $connection->user_b_id === $userId, 403);

        if (! $call->canExtend()) {
            return response()->json(['message' => 'Maximum extensions reached'], 422);
        }

        $call->update([
            'expires_at' => $call->expires_at->addMinutes(3),
            'extensions' => $call->extensions + 1,
        ]);

        return response()->json([
            'expires_at' => $call->fresh()->expires_at->toISOString(),
            'extensions' => $call->extensions,
        ]);
    }

    public function end(Request $request, Connection $connection, Call $call): JsonResponse
    {
        $userId = $request->user()->id;
        abort_unless($connection->user_a_id === $userId || $connection->user_b_id === $userId, 403);

        $call->update(['ended_at' => now()]);

        return response()->json(['message' => 'Call ended']);
    }
}
```

- [ ] **Step 7: Add routes**

Add to `routes/web.php` inside the auth middleware group:

```php
use App\Http\Controllers\CallController;

Route::post('/connections/{connection}/call', [CallController::class, 'start'])->name('connection.call.start');
Route::patch('/connections/{connection}/call/{call}/extend', [CallController::class, 'extend'])->name('connection.call.extend');
Route::patch('/connections/{connection}/call/{call}/end', [CallController::class, 'end'])->name('connection.call.end');
```

- [ ] **Step 8: Run migration and tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=CallControllerTest`
Expected: All 3 tests PASS

- [ ] **Step 9: Commit**

```bash
git add database/migrations/*calls* app/Models/Call.php app/Events/CallInitiated.php app/Http/Controllers/CallController.php routes/web.php tests/Feature/Http/CallControllerTest.php
git commit -m "feat: add CallController with 3-min calls, extensions, and signaling"
```

---

## Task 6: Run Full Suite & Lint

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
