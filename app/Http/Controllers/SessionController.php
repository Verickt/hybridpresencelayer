<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionCheckIn;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    public function index(Request $request, Event $event): Response
    {
        abort_unless($this->canViewSessions($request->user(), $event), 403);

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
        $user = $request->user();
        abort_unless($this->canViewSessions($user, $event), 403);

        $viewerParticipant = $event->participants()->whereKey($user->id)->first();
        $isOrganizer = $event->organizer_id === $user->id;
        $isParticipant = $viewerParticipant !== null;
        $isCheckedIn = $isParticipant && $session->hasActiveCheckInFor($user);

        $participants = SessionCheckIn::where('event_session_id', $session->id)
            ->whereNull('checked_out_at')
            ->with([
                'user.events' => fn ($query) => $query->whereKey($event->id),
            ])
            ->get()
            ->map(fn (SessionCheckIn $checkIn) => [
                'id' => $checkIn->user->id,
                'name' => $checkIn->user->name,
                'participant_type' => $checkIn->user->events->first()?->pivot?->participant_type,
            ]);

        $questions = $session->questions()
            ->with('user:id,name')
            ->withCount('votes')
            ->with([
                'votes' => fn ($query) => $query
                    ->where('user_id', $user->id)
                    ->select('id', 'session_question_id'),
            ])
            ->orderByDesc('votes_count')
            ->orderByDesc('id')
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
                'is_joinable' => $session->isJoinable(),
                'qa_enabled' => $session->qa_enabled,
                'reactions_enabled' => $session->reactions_enabled,
                'can_interact' => $session->canInteract(),
            ],
            'viewer' => [
                'is_organizer' => $isOrganizer,
                'participant_type' => $viewerParticipant?->pivot?->participant_type,
                'is_checked_in' => $isCheckedIn,
                'can_join' => $isParticipant && $session->isJoinable(),
                'can_interact' => $isParticipant && $isCheckedIn && $session->canInteract(),
            ],
            'participants' => $participants,
            'questions' => $questions->map(fn ($question) => [
                'id' => $question->id,
                'body' => $question->body,
                'user' => [
                    'id' => $question->user->id,
                    'name' => $question->user->name,
                ],
                'votes_count' => $question->votes_count,
                'viewer_has_voted' => $question->votes->isNotEmpty(),
            ]),
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
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

    private function canViewSessions(User $user, Event $event): bool
    {
        return $event->organizer_id === $user->id
            || $event->participants()->whereKey($user->id)->exists();
    }
}
