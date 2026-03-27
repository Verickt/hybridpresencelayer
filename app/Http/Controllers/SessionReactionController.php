<?php

namespace App\Http\Controllers;

use App\Events\SessionReactionSent;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionCheckIn;
use App\Models\SessionReaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionReactionController extends Controller
{
    public function store(Request $request, Event $event, EventSession $session): JsonResponse
    {
        $isCheckedIn = SessionCheckIn::where('user_id', $request->user()->id)
            ->where('event_session_id', $session->id)
            ->whereNull('checked_out_at')
            ->exists();

        abort_unless($isCheckedIn, 403);

        abort_unless($session->isLive(), 422, 'Session has ended');

        $validated = $request->validate([
            'type' => ['required', 'in:lightbulb,clap,question,fire,think'],
        ]);

        $reaction = SessionReaction::create([
            'user_id' => $request->user()->id,
            'event_session_id' => $session->id,
            'type' => $validated['type'],
        ]);

        SessionReactionSent::dispatch($session, $reaction);

        return response()->json(['message' => 'Reaction sent']);
    }
}
