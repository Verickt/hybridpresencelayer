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

        $user = $request->user();

        $sessions = $event->sessions()
            ->orderBy('starts_at')
            ->get()
            ->map(function (EventSession $s) use ($event, $user) {
                $activeCheckIns = $s->checkIns()->whereNull('checked_out_at')
                    ->with(['user.events' => fn ($q) => $q->whereKey($event->id)])
                    ->get();

                $physical = $activeCheckIns->filter(fn ($c) => $c->user->events->first()?->pivot?->participant_type === 'physical')->count();
                $remote = $activeCheckIns->count() - $physical;

                return [
                    'id' => $s->id,
                    'title' => $s->title,
                    'description' => $s->description,
                    'speaker' => $s->speaker,
                    'room' => $s->room,
                    'starts_at' => $s->starts_at->toISOString(),
                    'ends_at' => $s->ends_at->toISOString(),
                    'is_live' => $s->isLive(),
                    'qa_enabled' => $s->qa_enabled,
                    'attendee_count' => $activeCheckIns->count(),
                    'physical_count' => $physical,
                    'remote_count' => $remote,
                    'is_checked_in' => $s->hasActiveCheckInFor($user),
                ];
            });

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
        $isSpeaker = $session->speaker_user_id === $user->id;
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

        $questionsQuery = $session->questions()
            ->with(['user:id,name', 'replies' => fn ($q) => $q->with('user:id,name')->withCount('votes'), 'votes'])
            ->withCount('votes');

        if (! $isOrganizer && ! $isSpeaker) {
            $questionsQuery->where('is_hidden', false);
        }

        $questions = $questionsQuery
            ->orderByDesc('is_pinned')
            ->orderByDesc('votes_count')
            ->get()
            ->map(fn ($q) => [
                'id' => $q->id,
                'body' => $q->body,
                'user' => ['id' => $q->user->id, 'name' => $q->user->name],
                'votes_count' => $q->votes_count,
                'viewer_has_voted' => $q->votes->contains('user_id', $user->id),
                'is_answered' => $q->is_answered,
                'is_pinned' => $q->is_pinned,
                'is_hidden' => $q->is_hidden,
                'answered_by' => $q->answered_by,
                'replies' => $q->replies->map(fn ($r) => [
                    'id' => $r->id,
                    'body' => $r->body,
                    'user' => ['id' => $r->user->id, 'name' => $r->user->name],
                    'votes_count' => $r->votes_count,
                    'viewer_has_voted' => $r->votes->contains('user_id', $user->id),
                    'is_speaker' => $session->speaker_user_id === $r->user_id,
                    'is_organizer' => $event->organizer_id === $r->user_id,
                    'created_at' => $r->created_at->toISOString(),
                ]),
            ]);

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
                'speaker_user_id' => $session->speaker_user_id,
            ],
            'viewer' => [
                'is_organizer' => $isOrganizer,
                'is_speaker' => $isSpeaker,
                'is_moderator' => $isOrganizer || $isSpeaker,
                'participant_type' => $viewerParticipant?->pivot?->participant_type,
                'is_checked_in' => $isCheckedIn,
                'can_join' => $isParticipant && $session->isJoinable(),
                'can_interact' => $isParticipant && $isCheckedIn && $session->canInteract(),
            ],
            'participants' => $participants,
            'questions' => $questions,
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

        return redirect()->back();
    }

    public function destroy(Request $request, Event $event, EventSession $session): RedirectResponse
    {
        abort_unless($request->user()->id === $event->organizer_id, 403);

        $session->delete();

        return redirect()->back();
    }

    private function canViewSessions(User $user, Event $event): bool
    {
        return $event->organizer_id === $user->id
            || $event->participants()->whereKey($user->id)->exists();
    }
}
