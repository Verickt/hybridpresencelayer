# Plan 8: Notifications — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the notification system: in-app notification storage, push notifications via Web Push API, frequency limits, boundary respect (busy/DND), and user controls.

**Architecture:** A `notifications` table stores all in-app notifications. A `NotificationService` is the ONLY path for sending notifications — all controllers (including organizer announcements) must go through it. This ensures frequency limits, boundary respect (busy/DND), and deduplication are always enforced. Push notifications are sent via Laravel's notification system with a web-push channel. User preferences stored on the event_user pivot.

**Tech Stack:** Laravel 13, Web Push (VAPID), Pest v4

**Depends on:** Plan 1 (models), Plan 2 (presence)

## TDD Standard

- Start each task with failing tests before implementation.
- Cover negative and edge cases: DND/quiet mode, missing membership, deduplication, frequency limits, stale notifications, unauthorized reads, and preference update validation.
- For notification endpoints, add `assertInertia` or HTTP assertions for unread counts, payload shape, and hidden metadata.
- Add browser smoke coverage plus real browser tests for one visible notification flow and one blocked/suppressed path.

---

## Task 1: Notification Model & Migration

**Files:**
- Create: `database/migrations/xxxx_create_notifications_table.php` (use Laravel's built-in)
- Create: `database/migrations/xxxx_add_notification_prefs_to_event_user_table.php`
- Create: `app/Services/NotificationService.php`
- Create: `tests/Feature/Services/NotificationServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Services/NotificationServiceTest.php
<?php

use App\Models\Event;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Notifications\DatabaseNotification;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->user = User::factory()->create();
    $this->event->participants()->attach($this->user, [
        'participant_type' => 'physical',
        'status' => 'available',
        'notification_mode' => 'normal',
    ]);
    $this->service = app(NotificationService::class);
});

it('creates a notification', function () {
    $this->service->send($this->user, $this->event, 'ping', 'high', 'Alex wants to connect', ['ping_id' => 1]);

    expect($this->user->notifications()->count())->toBe(1);
});

it('skips low priority notifications when user is busy', function () {
    $this->user->events()->updateExistingPivot($this->event->id, ['status' => 'busy']);

    $this->service->send($this->user, $this->event, 'nudge', 'low', 'Check out these matches');

    expect($this->user->notifications()->count())->toBe(0);
});

it('still sends high priority when user is busy', function () {
    $this->user->events()->updateExistingPivot($this->event->id, ['status' => 'busy']);

    $this->service->send($this->user, $this->event, 'ping', 'high', 'Alex wants to connect');

    expect($this->user->notifications()->count())->toBe(1);
});

it('skips all notifications in DND mode', function () {
    $this->user->events()->updateExistingPivot($this->event->id, ['notification_mode' => 'dnd']);

    $this->service->send($this->user, $this->event, 'ping', 'high', 'Alex wants to connect');

    expect($this->user->notifications()->count())->toBe(0);
});

it('respects medium priority frequency limits', function () {
    // Send 4 medium notifications (max per hour)
    for ($i = 0; $i < 4; $i++) {
        $this->service->send($this->user, $this->event, 'suggestion', 'medium', "Suggestion {$i}");
    }

    // 5th should be blocked
    $this->service->send($this->user, $this->event, 'suggestion', 'medium', 'Suggestion 5');

    expect($this->user->notifications()->count())->toBe(4);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=NotificationServiceTest`
Expected: FAIL

- [ ] **Step 3: Add notification preferences to event_user pivot**

Run: `php artisan make:migration add_notification_prefs_to_event_user_table --no-interaction`

```php
Schema::table('event_user', function (Blueprint $table) {
    $table->string('notification_mode')->default('normal')->after('available_after_session');
});
```

- [ ] **Step 4: Update User model events relationship to include new pivot field**

In `app/Models/User.php`, add `'notification_mode'` to the `withPivot` array in the `events()` relationship.

- [ ] **Step 5: Create NotificationService**

```php
// app/Services/NotificationService.php
<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    private const FREQUENCY_LIMITS = [
        'high' => ['per_hour' => PHP_INT_MAX, 'per_day' => PHP_INT_MAX],
        'medium' => ['per_hour' => 4, 'per_day' => 20],
        'low' => ['per_hour' => 2, 'per_day' => 10],
    ];

    public function send(User $user, Event $event, string $type, string $priority, string $message, array $data = []): bool
    {
        $pivot = $user->events()->where('event_id', $event->id)->first()?->pivot;

        if (! $pivot) {
            return false;
        }

        // DND blocks everything
        if ($pivot->notification_mode === 'dnd') {
            return false;
        }

        // Quiet mode only allows high priority
        if ($pivot->notification_mode === 'quiet' && $priority !== 'high') {
            return false;
        }

        // Busy status blocks non-high priority
        if ($pivot->status === 'busy' && $priority !== 'high') {
            return false;
        }

        // Check frequency limits
        if (! $this->withinFrequencyLimit($user, $priority)) {
            return false;
        }

        // Store as database notification
        $user->notify(new \App\Notifications\InAppNotification(
            type: $type,
            priority: $priority,
            message: $message,
            eventId: $event->id,
            data: $data,
        ));

        return true;
    }

    private function withinFrequencyLimit(User $user, string $priority): bool
    {
        $limits = self::FREQUENCY_LIMITS[$priority] ?? self::FREQUENCY_LIMITS['low'];

        $hourCount = $user->notifications()
            ->where('created_at', '>', now()->subHour())
            ->whereJsonContains('data->priority', $priority)
            ->count();

        return $hourCount < $limits['per_hour'];
    }
}
```

- [ ] **Step 6: Create InAppNotification**

```php
// app/Notifications/InAppNotification.php
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InAppNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $type,
        public string $priority,
        public string $message,
        public int $eventId,
        public array $data = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'priority' => $this->priority,
            'message' => $this->message,
            'event_id' => $this->eventId,
            ...$this->data,
        ];
    }
}
```

- [ ] **Step 7: Run migration and tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=NotificationServiceTest`
Expected: All 5 tests PASS

- [ ] **Step 8: Commit**

```bash
git add database/migrations/*notification_prefs* app/Services/NotificationService.php app/Notifications/InAppNotification.php app/Models/User.php tests/Feature/Services/NotificationServiceTest.php
git commit -m "feat: add NotificationService with frequency limits, boundary respect, and DND"
```

---

## Task 2: Notification Controller

**Files:**
- Create: `app/Http/Controllers/NotificationController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Http/NotificationControllerTest.php`

- [ ] **Step 1: Write the failing test**

```php
// tests/Feature/Http/NotificationControllerTest.php
<?php

use App\Models\Event;
use App\Models\User;
use App\Notifications\InAppNotification;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->user = User::factory()->create();
    $this->event->participants()->attach($this->user, ['participant_type' => 'physical', 'status' => 'available']);
});

it('lists unread notifications', function () {
    $this->user->notify(new InAppNotification('ping', 'high', 'Alex pinged you', $this->event->id));

    $response = $this->actingAs($this->user)
        ->get(route('notifications.index'));

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('marks a notification as read', function () {
    $this->user->notify(new InAppNotification('ping', 'high', 'Alex pinged you', $this->event->id));
    $notification = $this->user->notifications()->first();

    $response = $this->actingAs($this->user)
        ->patch(route('notifications.read', $notification->id));

    $response->assertOk();
    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('returns unread count', function () {
    $this->user->notify(new InAppNotification('ping', 'high', 'Test 1', $this->event->id));
    $this->user->notify(new InAppNotification('match', 'high', 'Test 2', $this->event->id));

    $response = $this->actingAs($this->user)
        ->get(route('notifications.count'));

    $response->assertOk()
        ->assertJson(['count' => 2]);
});

it('updates notification preferences', function () {
    $response = $this->actingAs($this->user)
        ->patch(route('event.notification-prefs', $this->event), [
            'notification_mode' => 'quiet',
        ]);

    $response->assertOk();
    $pivot = $this->user->events()->where('event_id', $this->event->id)->first()->pivot;
    expect($pivot->notification_mode)->toBe('quiet');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=NotificationControllerTest`
Expected: FAIL

- [ ] **Step 3: Create NotificationController**

Run: `php artisan make:controller NotificationController --no-interaction`

```php
// app/Http/Controllers/NotificationController.php
<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->unreadNotifications()
            ->latest()
            ->take(20)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->data['type'] ?? 'unknown',
                'priority' => $n->data['priority'] ?? 'low',
                'message' => $n->data['message'] ?? '',
                'created_at' => $n->created_at->toISOString(),
                'data' => $n->data,
            ]);

        return response()->json(['data' => $notifications]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['message' => 'Marked as read']);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function updatePreferences(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'notification_mode' => ['required', 'in:normal,quiet,dnd'],
        ]);

        $request->user()->events()->updateExistingPivot($event->id, [
            'notification_mode' => $validated['notification_mode'],
        ]);

        return response()->json(['message' => 'Preferences updated']);
    }
}
```

- [ ] **Step 4: Add routes**

Add to `routes/web.php`:

```php
use App\Http\Controllers\NotificationController;

Route::middleware(['auth'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('/notifications/count', [NotificationController::class, 'unreadCount'])->name('notifications.count');
    Route::patch('/event/{event:slug}/notification-preferences', [NotificationController::class, 'updatePreferences'])->name('event.notification-prefs');
});
```

- [ ] **Step 5: Run tests**

Run: `php artisan migrate:fresh --no-interaction && php artisan test --compact --filter=NotificationControllerTest`
Expected: All 4 tests PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/NotificationController.php routes/web.php tests/Feature/Http/NotificationControllerTest.php
git commit -m "feat: add notification controller with read/unread, count, and preferences"
```

---

## Task 3: Run Full Suite & Lint

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
