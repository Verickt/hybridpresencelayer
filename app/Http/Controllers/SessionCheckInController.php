<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventSession;
use App\Services\PresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionCheckInController extends Controller
{
    public function store(Request $request, Event $event, EventSession $session, PresenceService $presenceService): JsonResponse
    {
        $presenceService->checkInToSession($request->user(), $event, $session);

        return response()->json(['message' => 'Checked in']);
    }

    public function destroy(Request $request, Event $event, EventSession $session, PresenceService $presenceService): JsonResponse
    {
        $presenceService->checkOutOfSession($request->user(), $event);

        return response()->json(['message' => 'Checked out']);
    }
}
