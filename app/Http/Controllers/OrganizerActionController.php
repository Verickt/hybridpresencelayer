<?php

namespace App\Http\Controllers;

use App\Events\ParticipantStatusChanged;
use App\Models\Event;
use App\Services\SuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizerActionController extends Controller
{
    public function announce(Request $request, Event $event): JsonResponse
    {
        abort_unless($request->user()->id === $event->organizer_id, 403);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        ParticipantStatusChanged::dispatch(
            $event,
            $request->user(),
            'available',
            "\xF0\x9F\x93\xA2 {$validated['message']}"
        );

        return response()->json(['message' => 'Ankündigung gesendet']);
    }

    public function serendipityWave(Request $request, Event $event, SuggestionService $suggestionService): JsonResponse
    {
        abort_unless($request->user()->id === $event->organizer_id, 403);

        $participants = $event->participants()
            ->wherePivot('status', '!=', 'busy')
            ->get();

        $generated = 0;
        foreach ($participants as $participant) {
            $suggestions = $suggestionService->generateForUser($participant, $event);
            $generated += $suggestions->count();
        }

        return response()->json(['message' => 'Serendipity-Welle ausgelöst', 'suggestions_generated' => $generated]);
    }
}
