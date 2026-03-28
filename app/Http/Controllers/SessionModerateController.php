<?php

namespace App\Http\Controllers;

use App\Events\SessionQuestionPinned;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\SessionQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionModerateController extends Controller
{
    public function pin(Request $request, Event $event, EventSession $session, SessionQuestion $question): JsonResponse
    {
        $this->authorizeModeration($request, $event, $session);

        $question->update(['is_pinned' => ! $question->is_pinned]);

        SessionQuestionPinned::dispatch($session, $question->fresh());

        return response()->json(['message' => $question->fresh()->is_pinned ? 'Question pinned' : 'Question unpinned']);
    }

    public function hide(Request $request, Event $event, EventSession $session, SessionQuestion $question): JsonResponse
    {
        $this->authorizeModeration($request, $event, $session);

        $question->update(['is_hidden' => ! $question->is_hidden]);

        return response()->json(['message' => $question->fresh()->is_hidden ? 'Question hidden' : 'Question visible']);
    }

    public function answer(Request $request, Event $event, EventSession $session, SessionQuestion $question): JsonResponse
    {
        $this->authorizeModeration($request, $event, $session);

        $isAnswered = ! $question->is_answered;

        $question->update([
            'is_answered' => $isAnswered,
            'answered_by' => $isAnswered ? $request->user()->id : null,
        ]);

        return response()->json(['message' => $isAnswered ? 'Marked as answered' : 'Unmarked as answered']);
    }

    private function authorizeModeration(Request $request, Event $event, EventSession $session): void
    {
        $isOrganizer = $event->organizer_id === $request->user()->id;
        $isSpeaker = $session->speaker_user_id === $request->user()->id;

        abort_unless($isOrganizer || $isSpeaker, 403);
    }
}
