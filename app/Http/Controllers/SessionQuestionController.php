<?php

namespace App\Http\Controllers;

use App\Events\SessionQuestionPosted;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionQuestion;
use App\Models\SessionQuestionVote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionQuestionController extends Controller
{
    public function store(Request $request, Event $event, EventSession $session): JsonResponse
    {
        abort_unless($session->hasActiveCheckInFor($request->user()), 403);
        abort_unless($session->qa_enabled, 403);
        abort_unless($session->canInteract(), 422, 'Sitzung ist beendet');

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:500'],
        ]);

        $question = SessionQuestion::create([
            'user_id' => $request->user()->id,
            'event_session_id' => $session->id,
            'body' => $validated['body'],
        ]);

        SessionQuestionPosted::dispatch($session, $question);

        return response()->json(['message' => 'Frage eingereicht']);
    }

    public function vote(Request $request, Event $event, EventSession $session, SessionQuestion $question): JsonResponse
    {
        abort_unless($session->hasActiveCheckInFor($request->user()), 403);
        abort_unless($session->qa_enabled, 403);
        abort_unless($session->canInteract(), 422, 'Sitzung ist beendet');

        $exists = SessionQuestionVote::where('session_question_id', $question->id)
            ->where('user_id', $request->user()->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Bereits abgestimmt'], 409);
        }

        SessionQuestionVote::create([
            'session_question_id' => $question->id,
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Stimme erfasst']);
    }
}
