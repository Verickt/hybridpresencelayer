<?php

use App\Models\Booth;
use App\Models\BoothThread;
use App\Models\BoothThreadReply;
use App\Models\BoothThreadVote;
use App\Models\Event;
use App\Models\User;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->booth = Booth::factory()->create([
        'event_id' => $this->event->id,
        'name' => 'Acme AI Booth',
    ]);

    $this->participant = User::factory()->create([
        'name' => 'Remote Visitor',
    ]);
    $this->staffMember = User::factory()->create([
        'name' => 'Booth Staff',
    ]);
    $this->outsider = User::factory()->create([
        'name' => 'Outsider',
    ]);

    $this->event->participants()->attach($this->participant, [
        'participant_type' => 'remote',
        'status' => 'available',
    ]);
    $this->event->participants()->attach($this->staffMember, [
        'participant_type' => 'physical',
        'status' => 'at_booth',
    ]);
    $this->booth->staff()->attach($this->staffMember);
});

it('creates a booth question thread for an event participant', function () {
    $response = $this->actingAs($this->participant)
        ->post(route('event.booths.threads.store', [$this->event, $this->booth]), [
            'body' => 'Will this demo be available later?',
        ]);

    $response->assertRedirect(route('event.booths.show', [$this->event, $this->booth]));

    expect(BoothThread::count())->toBe(1)
        ->and(BoothThread::first()->kind)->toBe('question')
        ->and(BoothThread::first()->follow_up_requested_at)->toBeNull();
});

it('validates booth question bodies', function () {
    $this->actingAs($this->participant)
        ->from(route('event.booths.show', [$this->event, $this->booth]))
        ->post(route('event.booths.threads.store', [$this->event, $this->booth]), [
            'body' => '',
        ])
        ->assertRedirect(route('event.booths.show', [$this->event, $this->booth]))
        ->assertSessionHasErrors('body');
});

it('allows booth staff to answer a thread', function () {
    $thread = BoothThread::factory()->create([
        'booth_id' => $this->booth->id,
        'user_id' => $this->participant->id,
        'kind' => 'question',
    ]);

    $response = $this->actingAs($this->staffMember)
        ->post(route('event.booths.threads.replies.store', [$this->event, $this->booth, $thread]), [
            'body' => 'Yes, we will post the recap after the event.',
        ]);

    $response->assertRedirect(route('event.booths.show', [$this->event, $this->booth]));

    expect(BoothThreadReply::count())->toBe(1)
        ->and(BoothThreadReply::first()->is_staff_answer)->toBeTrue();
});

it('forbids non-staff users from answering booth threads', function () {
    $thread = BoothThread::factory()->create([
        'booth_id' => $this->booth->id,
        'user_id' => $this->participant->id,
        'kind' => 'question',
    ]);

    $this->actingAs($this->participant)
        ->post(route('event.booths.threads.replies.store', [$this->event, $this->booth, $thread]), [
            'body' => 'I should not be able to answer this.',
        ])
        ->assertForbidden();
});

it('records a vote for a booth thread once per participant', function () {
    $thread = BoothThread::factory()->create([
        'booth_id' => $this->booth->id,
        'user_id' => $this->staffMember->id,
        'kind' => 'question',
    ]);

    $this->actingAs($this->participant)
        ->post(route('event.booths.threads.vote', [$this->event, $this->booth, $thread]))
        ->assertRedirect(route('event.booths.show', [$this->event, $this->booth]));

    $this->actingAs($this->participant)
        ->post(route('event.booths.threads.vote', [$this->event, $this->booth, $thread]))
        ->assertRedirect(route('event.booths.show', [$this->event, $this->booth]));

    expect(BoothThreadVote::count())->toBe(1);
});

it('lets a thread author request private follow up once', function () {
    $thread = BoothThread::factory()->create([
        'booth_id' => $this->booth->id,
        'user_id' => $this->participant->id,
        'kind' => 'question',
        'follow_up_requested_at' => null,
    ]);

    $this->actingAs($this->participant)
        ->patch(route('event.booths.threads.follow-up', [$this->event, $this->booth, $thread]))
        ->assertRedirect(route('event.booths.show', [$this->event, $this->booth]));

    expect($thread->fresh()->follow_up_requested_at)->not->toBeNull();
});

it('forbids follow-up requests from other participants', function () {
    $thread = BoothThread::factory()->create([
        'booth_id' => $this->booth->id,
        'user_id' => $this->staffMember->id,
        'kind' => 'question',
    ]);

    $this->actingAs($this->participant)
        ->patch(route('event.booths.threads.follow-up', [$this->event, $this->booth, $thread]))
        ->assertForbidden();
});

it('lets booth staff mark a thread as answered and pin it', function () {
    $thread = BoothThread::factory()->create([
        'booth_id' => $this->booth->id,
        'user_id' => $this->participant->id,
        'kind' => 'question',
        'is_answered' => false,
        'is_pinned' => false,
    ]);

    $this->actingAs($this->staffMember)
        ->patch(route('event.booths.threads.answer', [$this->event, $this->booth, $thread]))
        ->assertRedirect(route('event.booths.show', [$this->event, $this->booth]));

    $this->actingAs($this->staffMember)
        ->patch(route('event.booths.threads.pin', [$this->event, $this->booth, $thread]))
        ->assertRedirect(route('event.booths.show', [$this->event, $this->booth]));

    expect($thread->fresh())
        ->is_answered->toBeTrue()
        ->is_pinned->toBeTrue();
});
