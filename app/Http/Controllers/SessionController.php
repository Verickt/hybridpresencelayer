<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionCheckIn;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    public function index(Request $request, Event $event): Response
    {
        $sessions = $event->sessions()
            ->orderBy('starts_at')
            ->get()
            ->map(fn (EventSession $s) => [
                'id' => $s->id,
                'title' => $s->title,
                'description' => $s->description,
                'speaker' => $s->speaker,
                'room' => $s->room,
                'starts_at' => $s->starts_at->toISOString(),
                'ends_at' => $s->ends_at->toISOString(),
                'is_live' => $s->isLive(),
                'qa_enabled' => $s->qa_enabled,
                'attendee_count' => $s->checkIns()->whereNull('checked_out_at')->count(),
            ]);

        return Inertia::render('Event/Sessions', [
            'event' => ['id' => $event->id, 'name' => $event->name, 'slug' => $event->slug],
            'sessions' => $sessions,
        ]);
    }

    public function show(Request $request, Event $event, EventSession $session): Response
    {
        $participants = SessionCheckIn::where('event_session_id', $session->id)
            ->whereNull('checked_out_at')
            ->with('user')
            ->get()
            ->map(fn ($checkIn) => [
                'id' => $checkIn->user->id,
                'name' => $checkIn->user->name,
                'participant_type' => $checkIn->user->events()->where('event_id', $event->id)->first()?->pivot?->participant_type,
            ]);

        $questions = $session->questions()
            ->with('user:id,name')
            ->withCount('votes')
            ->orderByDesc('votes_count')
            ->get();

        return Inertia::render('Event/SessionDetail', [
            'event' => ['id' => $event->id, 'name' => $event->name, 'slug' => $event->slug],
            'session' => [
                'id' => $session->id,
                'title' => $session->title,
                'description' => $session->description,
                'speaker' => $session->speaker,
                'room' => $session->room,
                'starts_at' => $session->starts_at->toISOString(),
                'ends_at' => $session->ends_at->toISOString(),
                'is_live' => $session->isLive(),
                'qa_enabled' => $session->qa_enabled,
                'reactions_enabled' => $session->reactions_enabled,
            ],
            'participants' => $participants,
            'questions' => $questions,
        ]);
    }

    public function store(Request $request, Event $event)
    {
        abort_unless($request->user()->id === $event->organizer_id, 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'speaker' => ['nullable', 'string', 'max:255'],
            'room' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);

        $event->sessions()->create($validated);

        return redirect()->route('event.sessions', $event);
    }
}
