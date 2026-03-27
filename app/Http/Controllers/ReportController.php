<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function store(Request $request, Event $event, User $user): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        Report::create([
            'reporter_id' => $request->user()->id,
            'reported_id' => $user->id,
            'event_id' => $event->id,
            'reason' => $request->input('reason'),
        ]);

        return response()->json(['message' => 'Report submitted']);
    }
}
