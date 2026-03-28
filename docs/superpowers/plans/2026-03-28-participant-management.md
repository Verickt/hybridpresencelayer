# Participant Management Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Organizers can view and hard-delete event participants; self-delete flow already exists (fix password requirement for magic-link users).

**Architecture:** New `ParticipantController` with `index` (Inertia page) and `destroy` (hard-delete user). New `Event/Participants.vue` page with table + delete button. Fix existing self-delete to skip password validation when user has no password.

**Tech Stack:** Laravel 13, Pest 4, Inertia v3, Vue 3, Tailwind v4, Wayfinder

---

### Task 1: ParticipantController — index (test + implementation)

**Files:**
- Create: `tests/Feature/Http/ParticipantControllerTest.php`
- Create: `app/Http/Controllers/ParticipantController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Create the test file**

```bash
php artisan make:test Http/ParticipantControllerTest --pest --no-interaction
```

- [ ] **Step 2: Write failing test for index**

Write to `tests/Feature/Http/ParticipantControllerTest.php`:

```php
<?php

use App\Models\Event;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->organizer = User::factory()->create();
    $this->event = Event::factory()->live()->create(['organizer_id' => $this->organizer->id]);
    $this->participant = User::factory()->create(['name' => 'Test Participant']);
    $this->event->participants()->attach($this->participant, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);
});

it('shows participants list to the organizer', function () {
    $this->actingAs($this->organizer)
        ->get(route('event.participants', $this->event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Event/Participants')
            ->has('event', fn (Assert $e) => $e
                ->where('id', $this->event->id)
                ->etc()
            )
            ->has('participants', 1)
            ->has('participants.0', fn (Assert $p) => $p
                ->where('id', $this->participant->id)
                ->where('name', 'Test Participant')
                ->has('pivot')
                ->etc()
            )
        );
});

it('denies non-organizer access to participants list', function () {
    $nonOrganizer = User::factory()->create();

    $this->actingAs($nonOrganizer)
        ->get(route('event.participants', $this->event))
        ->assertForbidden();
});
```

- [ ] **Step 3: Run test to verify it fails**

```bash
php artisan test --compact --filter=ParticipantControllerTest
```

Expected: FAIL (route not defined)

- [ ] **Step 4: Create controller and route**

```bash
php artisan make:controller ParticipantController --no-interaction
```

Write `app/Http/Controllers/ParticipantController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ParticipantController extends Controller
{
    public function index(Request $request, Event $event): Response
    {
        abort_unless($request->user()->id === $event->organizer_id, 403);

        $participants = $event->participants()
            ->select('users.id', 'users.name', 'users.email', 'users.created_at')
            ->get();

        return Inertia::render('Event/Participants', [
            'event' => $event->only('id', 'name', 'slug'),
            'participants' => $participants,
        ]);
    }
}
```

Add to `routes/web.php` inside the `auth` middleware group, after the dashboard route (line 126):

```php
Route::get('/event/{event:slug}/participants', [ParticipantController::class, 'index'])->name('event.participants');
```

Add the import at the top of `routes/web.php`:

```php
use App\Http\Controllers\ParticipantController;
```

- [ ] **Step 5: Run test to verify it passes**

```bash
php artisan test --compact --filter=ParticipantControllerTest
```

Expected: PASS

- [ ] **Step 6: Lint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/ParticipantController.php tests/Feature/Http/ParticipantControllerTest.php routes/web.php
git commit -m "feat: add ParticipantController index with organizer authorization"
```

---

### Task 2: ParticipantController — destroy (test + implementation)

**Files:**
- Modify: `tests/Feature/Http/ParticipantControllerTest.php`
- Modify: `app/Http/Controllers/ParticipantController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Write failing tests for destroy**

Append to `tests/Feature/Http/ParticipantControllerTest.php`:

```php
it('allows organizer to delete a participant', function () {
    $this->actingAs($this->organizer)
        ->delete(route('event.participants.destroy', [$this->event, $this->participant]))
        ->assertRedirect(route('event.participants', $this->event));

    $this->assertDatabaseMissing('users', ['id' => $this->participant->id]);
    $this->assertDatabaseMissing('event_user', ['user_id' => $this->participant->id]);
});

it('prevents organizer from deleting themselves', function () {
    $this->event->participants()->attach($this->organizer, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);

    $this->actingAs($this->organizer)
        ->delete(route('event.participants.destroy', [$this->event, $this->organizer]))
        ->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $this->organizer->id]);
});

it('prevents non-organizer from deleting participants', function () {
    $nonOrganizer = User::factory()->create();

    $this->actingAs($nonOrganizer)
        ->delete(route('event.participants.destroy', [$this->event, $this->participant]))
        ->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $this->participant->id]);
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --compact --filter=ParticipantControllerTest
```

Expected: FAIL (route not defined)

- [ ] **Step 3: Add destroy method and route**

Add to `ParticipantController`:

```php
use Illuminate\Http\RedirectResponse;
use App\Models\User;

public function destroy(Request $request, Event $event, User $user): RedirectResponse
{
    abort_unless($request->user()->id === $event->organizer_id, 403);
    abort_unless($request->user()->id !== $user->id, 403);

    $user->delete();

    return redirect()->route('event.participants', $event);
}
```

Add to `routes/web.php` after the participants index route:

```php
Route::delete('/event/{event:slug}/participants/{user}', [ParticipantController::class, 'destroy'])->name('event.participants.destroy');
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
php artisan test --compact --filter=ParticipantControllerTest
```

Expected: PASS

- [ ] **Step 5: Lint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/ParticipantController.php tests/Feature/Http/ParticipantControllerTest.php routes/web.php
git commit -m "feat: add participant destroy with organizer-only authorization"
```

---

### Task 3: Participants Vue page

**Files:**
- Create: `resources/js/pages/Event/Participants.vue`

- [ ] **Step 1: Generate Wayfinder routes**

```bash
php artisan wayfinder:generate
```

- [ ] **Step 2: Create the Participants page**

Write `resources/js/pages/Event/Participants.vue`:

```vue
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Trash2, Users } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import Heading from '@/components/Heading.vue';
import ParticipantController from '@/actions/App/Http/Controllers/ParticipantController';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    event: { id: number; name: string; slug: string };
    participants: Array<{
        id: number;
        name: string;
        email: string | null;
        pivot: {
            participant_type: string;
            status: string;
            last_active_at: string | null;
        };
    }>;
}>();

const confirmingDelete = ref<number | null>(null);

function deleteParticipant(userId: number) {
    router.delete(ParticipantController.destroy.url({ event: props.event.slug, user: userId }), {
        preserveScroll: true,
        onSuccess: () => { confirmingDelete.value = null; },
    });
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head :title="`Teilnehmer — ${event.name}`" />

        <div class="flex items-center gap-3">
            <Users class="size-5 text-indigo-600" />
            <h1 class="text-2xl font-bold">Teilnehmer</h1>
            <span class="text-sm text-muted-foreground">({{ participants.length }})</span>
        </div>

        <Card class="border-border/70 py-0 shadow-sm">
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border/50 text-left text-muted-foreground">
                                <th class="px-4 py-3 font-medium">Name</th>
                                <th class="px-4 py-3 font-medium">E-Mail</th>
                                <th class="px-4 py-3 font-medium text-center">Typ</th>
                                <th class="px-4 py-3 font-medium text-center">Status</th>
                                <th class="px-4 py-3 font-medium text-right">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="participant in participants"
                                :key="participant.id"
                                class="border-b border-border/30"
                            >
                                <td class="px-4 py-3 font-medium">{{ participant.name }}</td>
                                <td class="px-4 py-3 text-muted-foreground">{{ participant.email ?? '—' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="participant.pivot.participant_type === 'physical'
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                            : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'"
                                    >
                                        {{ participant.pivot.participant_type === 'physical' ? 'Vor Ort' : 'Remote' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center text-muted-foreground">{{ participant.pivot.status }}</td>
                                <td class="px-4 py-3 text-right">
                                    <Button
                                        v-if="confirmingDelete !== participant.id"
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive hover:text-destructive"
                                        @click="confirmingDelete = participant.id"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                    <div v-else class="flex items-center justify-end gap-1">
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            @click="deleteParticipant(participant.id)"
                                        >
                                            Löschen
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="confirmingDelete = null"
                                        >
                                            Abbrechen
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="participants.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                                    Noch keine Teilnehmer.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
```

- [ ] **Step 3: Build frontend**

```bash
npm run build
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/pages/Event/Participants.vue
git commit -m "feat: add Participants.vue page with delete confirmation"
```

---

### Task 4: Link from Dashboard + Wayfinder

**Files:**
- Modify: `resources/js/pages/Event/Dashboard.vue`

- [ ] **Step 1: Add Participants link to Dashboard**

Add a "Teilnehmer verwalten" button in the Dashboard header area (next to the existing action buttons), linking to the participants page:

```vue
<!-- Add import at top of script -->
import ParticipantController from '@/actions/App/Http/Controllers/ParticipantController';
```

Add button in the `<div class="flex gap-2">` section (after the Ankündigen button):

```vue
<Button
    variant="outline"
    class="rounded-full"
    as="a"
    :href="ParticipantController.index.url({ event: event.slug })"
>
    <Users class="mr-1 size-4" />
    Teilnehmer
</Button>
```

- [ ] **Step 2: Regenerate Wayfinder and build**

```bash
php artisan wayfinder:generate
npm run build
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/Event/Dashboard.vue resources/js/actions/ resources/js/routes/
git commit -m "feat: link participants page from organizer dashboard"
```

---

### Task 5: Fix self-delete for passwordless users

**Files:**
- Modify: `tests/Feature/Http/ParticipantControllerTest.php` (or a new test file)
- Modify: `app/Http/Requests/Settings/ProfileDeleteRequest.php`
- Modify: `resources/js/components/DeleteUser.vue`

- [ ] **Step 1: Write failing test for passwordless self-delete**

Create `tests/Feature/Http/ProfileDeleteTest.php`:

```bash
php artisan make:test Http/ProfileDeleteTest --pest --no-interaction
```

Write:

```php
<?php

use App\Models\User;

it('allows a passwordless user to delete their account without providing a password', function () {
    $user = User::factory()->create(['password' => null]);

    $this->actingAs($user)
        ->delete(route('profile.destroy'))
        ->assertRedirect('/');

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

it('requires password from users who have one', function () {
    $user = User::factory()->create(['password' => bcrypt('secret')]);

    $this->actingAs($user)
        ->delete(route('profile.destroy'), ['password' => ''])
        ->assertSessionHasErrors('password');

    $this->assertDatabaseHas('users', ['id' => $user->id]);
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=ProfileDeleteTest
```

Expected: FAIL (passwordless user gets validation error)

- [ ] **Step 3: Fix ProfileDeleteRequest to skip password when user has none**

Update `app/Http/Requests/Settings/ProfileDeleteRequest.php`:

```php
<?php

namespace App\Http\Requests\Settings;

use App\Concerns\PasswordValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileDeleteRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->user()->password === null) {
            return [];
        }

        return [
            'password' => $this->currentPasswordRules(),
        ];
    }
}
```

- [ ] **Step 4: Update DeleteUser.vue to hide password field for passwordless users**

The `DeleteUser.vue` component needs to know if the user has a password. Add a `hasPassword` prop passed from the settings profile page, or check `auth.user`. The simplest approach: conditionally show the password field.

In `resources/js/components/DeleteUser.vue`, wrap the password field:

```vue
<div v-if="user.has_password" class="grid gap-2">
    <!-- existing password input -->
</div>
```

This requires passing `has_password` in the shared auth user data. Add to the `HandleInertiaRequests` middleware's `share` method:

```php
'has_password' => $user ? $user->password !== null : false,
```

- [ ] **Step 5: Run tests to verify they pass**

```bash
php artisan test --compact --filter=ProfileDeleteTest
```

Expected: PASS

- [ ] **Step 6: Lint and build**

```bash
vendor/bin/pint --dirty --format agent
npm run build
```

- [ ] **Step 7: Commit**

```bash
git add app/Http/Requests/Settings/ProfileDeleteRequest.php resources/js/components/DeleteUser.vue tests/Feature/Http/ProfileDeleteTest.php app/Http/Middleware/HandleInertiaRequests.php
git commit -m "fix: allow passwordless users to delete their account"
```
