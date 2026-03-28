<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function typeSelection(Request $request, Event $event): Response
    {
        $pivot = $request->user()->events()->where('event_id', $event->id)->first()?->pivot;

        return Inertia::render('Event/Onboarding/TypeSelection', [
            'event' => $this->eventProps($event),
            'currentType' => $pivot?->participant_type,
            'userName' => $request->user()->name,
        ]);
    }

    public function saveType(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'participant_type' => ['required', 'in:physical,remote'],
        ]);

        $request->user()->events()->updateExistingPivot($event->id, [
            'participant_type' => $validated['participant_type'],
        ]);

        return redirect()->route('event.onboarding.tags', $event);
    }

    public function interestTags(Request $request, Event $event): Response
    {
        $selectedIds = $request->user()->interestTags()
            ->wherePivot('event_id', $event->id)
            ->pluck('interest_tags.id');

        return Inertia::render('Event/Onboarding/InterestTags', [
            'event' => $this->eventProps($event),
            'tags' => $event->interestTags()->get()->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
            ]),
            'selectedIds' => $selectedIds,
        ]);
    }

    public function saveTags(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'tag_ids' => ['required', 'array', 'min:3', 'max:5'],
            'tag_ids.*' => ['integer', 'exists:interest_tags,id'],
        ]);

        // Sync tags for this event
        $syncData = collect($validated['tag_ids'])->mapWithKeys(fn ($tagId) => [
            $tagId => ['event_id' => $event->id],
        ])->all();

        // Remove existing tags for this event first, then attach new ones
        $request->user()->interestTags()
            ->wherePivot('event_id', $event->id)
            ->detach();

        $request->user()->interestTags()->attach($syncData);

        return redirect()->route('event.onboarding.icebreaker', $event);
    }

    public function icebreaker(Request $request, Event $event): Response
    {
        $pivot = $request->user()->events()->where('event_id', $event->id)->first()?->pivot;

        return Inertia::render('Event/Onboarding/IcebreakerSelection', [
            'event' => $this->eventProps($event),
            'questions' => $event->icebreakerQuestions()->get()->map(fn ($q) => [
                'id' => $q->id,
                'text' => $q->question,
            ]),
            'currentAnswer' => $pivot?->icebreaker_answer,
        ]);
    }

    public function saveIcebreaker(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'icebreaker_answer' => ['nullable', 'string', 'max:500'],
        ]);

        $request->user()->events()->updateExistingPivot($event->id, [
            'icebreaker_answer' => $validated['icebreaker_answer'],
        ]);

        return redirect()->route('event.onboarding.email', $event);
    }

    public function email(Request $request, Event $event): Response
    {
        return Inertia::render('Event/Onboarding/EmailCollection', [
            'event' => $this->eventProps($event),
            'currentEmail' => $request->user()->email,
        ]);
    }

    public function saveEmail(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,'.$request->user()->id],
        ]);

        if ($validated['email']) {
            $request->user()->update(['email' => $validated['email']]);
        }

        return redirect()->route('event.onboarding.ready', $event);
    }

    public function ready(Request $request, Event $event): Response
    {
        $pivot = $request->user()->events()->where('event_id', $event->id)->first()?->pivot;
        $tags = $request->user()->interestTags()
            ->wherePivot('event_id', $event->id)
            ->pluck('name');

        return Inertia::render('Event/Onboarding/ReadyScreen', [
            'event' => $this->eventProps($event),
            'user' => [
                'name' => $request->user()->name,
                'participant_type' => $pivot?->participant_type,
                'interest_tags' => $tags,
            ],
        ]);
    }

    private function eventProps(Event $event): array
    {
        return [
            'id' => $event->id,
            'name' => $event->name,
            'slug' => $event->slug,
        ];
    }
}
