<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Suggestion;
use App\Services\SuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SuggestionController extends Controller
{
    public function index(Request $request, Event $event): Response
    {
        $user = $request->user();

        abort_unless($event->participants()->where('users.id', $user->id)->exists(), 403);

        $suggestions = Suggestion::forUser($user)
            ->where('event_id', $event->id)
            ->active()
            ->with('suggestedUser')
            ->orderByDesc('score')
            ->get()
            ->map(fn (Suggestion $s) => [
                'id' => $s->id,
                'suggested_user' => [
                    'id' => $s->suggestedUser->id,
                    'name' => $s->suggestedUser->name,
                    'company' => $s->suggestedUser->company,
                    'participant_type' => $s->suggestedUser->events()
                        ->where('event_id', $event->id)
                        ->first()?->pivot?->participant_type,
                ],
                'score' => $s->score,
                'reason' => $s->reason,
                'expires_at' => $s->expires_at->toISOString(),
            ]);

        return Inertia::render('Event/Suggestions', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'slug' => $event->slug,
            ],
            'suggestions' => $suggestions,
        ]);
    }

    public function decline(Request $request, Event $event, Suggestion $suggestion, SuggestionService $suggestionService): JsonResponse
    {
        abort_unless($suggestion->suggested_to_id === $request->user()->id, 403);
        abort_if($suggestion->status !== 'pending', 409);

        $suggestionService->decline($suggestion);

        return response()->json(['message' => 'Vorschlag abgelehnt']);
    }

    public function accept(Request $request, Event $event, Suggestion $suggestion, SuggestionService $suggestionService): JsonResponse
    {
        abort_unless($suggestion->suggested_to_id === $request->user()->id, 403);
        abort_if($suggestion->status !== 'pending', 409);
        abort_if($suggestion->isExpired(), 409);

        $suggestionService->accept($suggestion);

        return response()->json(['message' => 'Vorschlag angenommen']);
    }
}
