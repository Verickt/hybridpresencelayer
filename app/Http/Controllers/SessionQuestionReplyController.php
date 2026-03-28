<?php

namespace App\Http\Controllers;

use App\Events\SessionQuestionReplyPosted;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionQuestion;
use App\Models\SessionQuestionReply;
use App\Models\SessionQuestionReplyVote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionQuestionReplyController extends Controller
{
    public function store(Request $request, Event $event, EventSession $session, SessionQuestion $question): JsonResponse
    {
        abort_unless($session->hasActiveCheckInFor($request->user()), 403);
        abort_unless($session->qa_enabled, 403);
        abort_unless($session->canInteract(), 422, 'Session has ended');

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:500'],
        ]);

        $reply = SessionQuestionReply::create([
            'session_question_id' => $question->id,
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        SessionQuestionReplyPosted::dispatch($session, $reply);

        return response()->json(['message' => 'Reply posted']);
    }

    public function vote(Request $request, Event $event, EventSession $session, SessionQuestion $question, SessionQuestionReply $reply): JsonResponse
    {
        abort_unless($session->hasActiveCheckInFor($request->user()), 403);
        abort_unless($session->qa_enabled, 403);

        $exists = SessionQuestionReplyVote::where('session_question_reply_id', $reply->id)
            ->where('user_id', $request->user()->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Already voted'], 409);
        }

        SessionQuestionReplyVote::create([
            'session_question_reply_id' => $reply->id,
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Vote recorded']);
    }
}
