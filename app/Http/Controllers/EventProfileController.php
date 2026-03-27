<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventProfileController extends Controller
{
    public function __invoke(Request $request, Event $event): Response
    {
        $user = $request->user();
        $pivot = $user->events()->where('event_id', $event->id)->first()?->pivot;
        $tags = $user->interestTags()->wherePivot('event_id', $event->id)->pluck('name');

        return Inertia::render('Event/Profile', [
            'event' => ['id' => $event->id, 'name' => $event->name, 'slug' => $event->slug],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company' => $user->company,
                'role_title' => $user->role_title,
                'intent' => $user->intent,
                'participant_type' => $pivot?->participant_type,
                'status' => $pivot?->status,
                'icebreaker_answer' => $pivot?->icebreaker_answer,
                'notification_mode' => $pivot?->notification_mode ?? 'normal',
                'is_invisible' => $user->is_invisible,
            ],
            'interestTags' => $tags,
        ]);
    }
}
