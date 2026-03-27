<?php

namespace App\Http\Controllers;

use App\Models\Booth;
use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BoothController extends Controller
{
    public function index(Request $request, Event $event): Response
    {
        $userTagIds = $request->user()->interestTags()
            ->wherePivot('event_id', $event->id)
            ->pluck('interest_tags.id');

        $booths = $event->booths()
            ->with(['interestTags', 'staff:id,name'])
            ->withCount(['visits' => fn ($q) => $q->where('is_anonymous', false)])
            ->get()
            ->map(function (Booth $booth) use ($userTagIds) {
                $boothTagIds = $booth->interestTags->pluck('id');
                $relevance = $boothTagIds->intersect($userTagIds)->count();

                return [
                    'id' => $booth->id,
                    'name' => $booth->name,
                    'company' => $booth->company,
                    'description' => $booth->description,
                    'interest_tags' => $booth->interestTags->pluck('name'),
                    'visitor_count' => $booth->visits_count,
                    'staff' => $booth->staff->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
                    'relevance' => $relevance,
                ];
            })
            ->sortByDesc('relevance')
            ->values();

        return Inertia::render('Event/Booths', [
            'event' => ['id' => $event->id, 'name' => $event->name, 'slug' => $event->slug],
            'booths' => $booths,
        ]);
    }

    public function show(Request $request, Event $event, Booth $booth): Response
    {
        $visitors = $booth->visits()
            ->where('is_anonymous', false)
            ->whereNull('left_at')
            ->with('user:id,name,company')
            ->get()
            ->map(fn ($v) => [
                'id' => $v->user->id,
                'name' => $v->user->name,
                'company' => $v->user->company,
                'participant_type' => $v->participant_type,
                'entered_at' => $v->entered_at->toISOString(),
            ]);

        $staff = $booth->staff()->get()->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'status' => $s->events()->where('event_id', $event->id)->first()?->pivot?->status,
        ]);

        return Inertia::render('Event/BoothDetail', [
            'event' => ['id' => $event->id, 'name' => $event->name, 'slug' => $event->slug],
            'booth' => [
                'id' => $booth->id,
                'name' => $booth->name,
                'company' => $booth->company,
                'description' => $booth->description,
                'content_links' => $booth->content_links,
                'interest_tags' => $booth->interestTags->pluck('name'),
            ],
            'visitors' => $visitors,
            'staff' => $staff,
        ]);
    }
}
