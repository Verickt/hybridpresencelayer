<?php

namespace App\Http\Controllers;

use App\Models\Booth;
use App\Models\BoothVisit;
use App\Models\Event;
use App\Models\SessionCheckIn;
use App\Services\PresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoothVisitController extends Controller
{
    public function store(Request $request, Event $event, Booth $booth, PresenceService $presenceService): JsonResponse
    {
        $user = $request->user();
        $isAnonymous = $request->boolean('anonymous', false);

        $pivot = $user->events()->where('event_id', $event->id)->first()?->pivot;

        $lastSessionId = null;
        if ($pivot?->status === 'in_session') {
            $lastCheckIn = SessionCheckIn::where('user_id', $user->id)
                ->whereNull('checked_out_at')
                ->first();
            $lastSessionId = $lastCheckIn?->event_session_id;
        }

        BoothVisit::create([
            'user_id' => $user->id,
            'booth_id' => $booth->id,
            'is_anonymous' => $isAnonymous,
            'participant_type' => $pivot?->participant_type,
            'from_session_id' => $lastSessionId,
            'entered_at' => now(),
        ]);

        if (! $isAnonymous) {
            $presenceService->checkInToBooth($user, $event, $booth);
        }

        return response()->json(['message' => 'Checked in']);
    }

    public function destroy(Request $request, Event $event, Booth $booth, PresenceService $presenceService): JsonResponse
    {
        BoothVisit::where('user_id', $request->user()->id)
            ->where('booth_id', $booth->id)
            ->whereNull('left_at')
            ->update(['left_at' => now()]);

        $presenceService->updateStatus($request->user(), $event, 'available');

        $request->user()->events()->updateExistingPivot($event->id, [
            'context_badge' => null,
        ]);

        return response()->json(['message' => 'Checked out']);
    }
}
