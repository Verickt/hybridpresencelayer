<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PresenceFeedController extends Controller
{
    public function __invoke(Request $request, Event $event): Response
    {
        abort_unless(
            $request->user()->events()->where('event_id', $event->id)->exists(),
            403
        );

        $blockedIds = Block::where('blocker_id', $request->user()->id)
            ->where('event_id', $event->id)
            ->pluck('blocked_id');

        $blockedByIds = Block::where('blocked_id', $request->user()->id)
            ->where('event_id', $event->id)
            ->pluck('blocker_id');

        $excludeIds = $blockedIds->merge($blockedByIds)->unique();

        $query = $event->participants()
            ->where('users.is_invisible', false)
            ->whereNotIn('users.id', $excludeIds);

        $type = $request->input('type', 'all');
        $status = $request->input('status', 'all');

        if ($type !== 'all' && $request->filled('type')) {
            $query->wherePivot('participant_type', $type);
        }

        if ($status !== 'all' && $request->filled('status')) {
            $query->wherePivot('status', $status);
        }

        if ($request->filled('tag')) {
            $tagId = $request->input('tag');
            $query->whereHas('interestTags', fn ($q) => $q->where('interest_tags.id', $tagId)
                ->where('user_interest_tag.event_id', $event->id)
            );
        }

        $participants = $query->orderBy('users.id', 'desc')
            ->with(['interestTags' => fn ($q) => $q->wherePivot('event_id', $event->id)])
            ->get()
            ->map(fn ($participant) => [
                'id' => $participant->id,
                'name' => $participant->name,
                'company' => $participant->company,
                'role_title' => $participant->role_title,
                'intent' => $participant->intent,
                'participant_type' => $participant->pivot->participant_type,
                'status' => $participant->pivot->status,
                'context_badge' => $participant->pivot->context_badge,
                'icebreaker_answer' => $participant->pivot->icebreaker_answer,
                'open_to_call' => $participant->pivot->open_to_call,
                'last_active_at' => $participant->pivot->last_active_at,
                'interest_tags' => $participant->interestTags->pluck('name'),
            ]);

        return Inertia::render('Event/Feed', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'slug' => $event->slug,
            ],
            'participants' => $participants,
            'filters' => [
                'type' => $type,
                'status' => $status,
                'tag' => $request->input('tag'),
            ],
        ]);
    }
}
