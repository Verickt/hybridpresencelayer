<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, Event $event, DashboardService $dashboardService): Response
    {
        abort_unless($request->user()->id === $event->organizer_id, 403);

        $sessions = $event->sessions()->orderBy('starts_at')->get()->map(fn ($s) => [
            'id' => $s->id,
            'title' => $s->title,
            'speaker' => $s->speaker,
            'room' => $s->room,
            'starts_at' => $s->starts_at->toISOString(),
            'ends_at' => $s->ends_at->toISOString(),
            'qa_enabled' => $s->qa_enabled,
            'reactions_enabled' => $s->reactions_enabled,
        ]);

        $booths = $event->booths()->with('staff:id,name')->get()->map(fn ($b) => [
            'id' => $b->id,
            'name' => $b->name,
            'company' => $b->company,
            'description' => $b->description,
            'staff' => $b->staff->pluck('name'),
        ]);

        $tags = $event->interestTags()->pluck('name', 'interest_tags.id');
        $icebreakers = $event->icebreakerQuestions()->get(['id', 'question']);

        return Inertia::render('Event/Dashboard', [
            'event' => ['id' => $event->id, 'name' => $event->name, 'slug' => $event->slug],
            'overview' => $dashboardService->overview($event),
            'sessionAnalytics' => $dashboardService->sessionAnalytics($event),
            'boothPerformance' => $dashboardService->boothPerformance($event),
            'sessions' => $sessions,
            'booths' => $booths,
            'interestTags' => $tags,
            'icebreakers' => $icebreakers,
        ]);
    }
}
