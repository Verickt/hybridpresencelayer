<?php

use App\Models\Booth;
use App\Models\BoothDemo;
use App\Models\BoothThread;
use App\Models\Event;
use App\Models\InterestTag;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->event = Event::factory()->live()->create();
    $this->otherEvent = Event::factory()->live()->create();
    $this->participant = User::factory()->create([
        'name' => 'Visitor One',
    ]);
    $this->event->participants()->attach($this->participant, [
        'participant_type' => 'physical',
        'status' => 'available',
    ]);

    $this->interestTag = InterestTag::factory()->create();
    $this->participant->interestTags()->attach($this->interestTag, [
        'event_id' => $this->event->id,
    ]);

    $this->featuredBooth = Booth::factory()->create([
        'event_id' => $this->event->id,
        'name' => 'High Match Booth',
        'company' => 'High Match AG',
    ]);
    $this->featuredBooth->interestTags()->attach($this->interestTag);

    $this->secondaryBooth = Booth::factory()->create([
        'event_id' => $this->event->id,
        'name' => 'Secondary Booth',
        'company' => 'Secondary AG',
    ]);
});

it('redirects guests to the login screen when browsing booth routes', function () {
    $this->get(route('event.booths', $this->event))
        ->assertRedirect(route('login'));
});

it('lists booths sorted by relevance and keeps the payload minimal', function () {
    $this->actingAs($this->participant)
        ->get(route('event.booths', $this->event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Event/Booths')
            ->has('booths', 2)
            ->where('booths.0.name', 'High Match Booth')
            ->where('booths.0.company', 'High Match AG')
            ->where('booths.1.name', 'Secondary Booth')
            ->missing('booths.0.secret')
        );
});

it('shows booth detail with visible visitors only and staff metadata', function () {
    $visibleVisitor = User::factory()->create([
        'name' => 'Visible Visitor',
    ]);
    $anonymousVisitor = User::factory()->create([
        'name' => 'Anonymous Visitor',
    ]);

    $this->featuredBooth->staff()->attach($this->participant);
    $liveDemo = BoothDemo::factory()->create([
        'booth_id' => $this->featuredBooth->id,
        'started_by_user_id' => $this->participant->id,
        'title' => 'Live walkthrough',
        'status' => 'live',
    ]);
    $demoPrompt = BoothThread::factory()->create([
        'booth_id' => $this->featuredBooth->id,
        'booth_demo_id' => $liveDemo->id,
        'user_id' => $this->participant->id,
        'kind' => 'demo_prompt',
        'body' => 'Ask us about the platform architecture.',
        'is_pinned' => true,
    ]);
    $pinnedQuestion = BoothThread::factory()->create([
        'booth_id' => $this->featuredBooth->id,
        'user_id' => $visibleVisitor->id,
        'kind' => 'question',
        'body' => 'Can I get the recap deck later?',
        'is_pinned' => true,
    ]);
    $normalQuestion = BoothThread::factory()->create([
        'booth_id' => $this->featuredBooth->id,
        'user_id' => $visibleVisitor->id,
        'kind' => 'question',
        'body' => 'Do you support remote follow-up?',
    ]);
    $this->featuredBooth->visits()->create([
        'user_id' => $visibleVisitor->id,
        'is_anonymous' => false,
        'participant_type' => 'physical',
        'entered_at' => now()->subMinutes(7),
    ]);
    $this->featuredBooth->visits()->create([
        'user_id' => $anonymousVisitor->id,
        'is_anonymous' => true,
        'participant_type' => 'remote',
        'entered_at' => now()->subMinutes(3),
    ]);

    $this->actingAs($this->participant)
        ->get(route('event.booths.show', [$this->event, $this->featuredBooth]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Event/BoothDetail')
            ->where('booth.name', 'High Match Booth')
            ->where('viewer.is_staff', true)
            ->where('viewer.can_post', true)
            ->where('active_demo.id', $liveDemo->id)
            ->where('active_demo.prompt_thread.id', $demoPrompt->id)
            ->where('pinned_thread.id', $pinnedQuestion->id)
            ->where('threads.0.id', $normalQuestion->id)
            ->where('staff.0.name', 'Visitor One')
            ->has('visitors', 1)
            ->where('visitors.0.name', 'Visible Visitor')
            ->missing('visitors.0.is_anonymous')
        );
});

it('returns not found when a booth from another event is requested', function () {
    $this->actingAs($this->participant)
        ->get(route('event.booths.show', [$this->otherEvent, $this->featuredBooth]))
        ->assertNotFound();
});
