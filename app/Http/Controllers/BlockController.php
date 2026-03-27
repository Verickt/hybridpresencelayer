<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    public function store(Request $request, Event $event, User $user): JsonResponse
    {
        Block::firstOrCreate([
            'blocker_id' => $request->user()->id,
            'blocked_id' => $user->id,
            'event_id' => $event->id,
        ], [
            'reason' => $request->input('reason'),
        ]);

        return response()->json(['message' => 'User blocked']);
    }

    public function destroy(Request $request, Event $event, User $user): JsonResponse
    {
        Block::where('blocker_id', $request->user()->id)
            ->where('blocked_id', $user->id)
            ->where('event_id', $event->id)
            ->delete();

        return response()->json(['message' => 'User unblocked']);
    }
}
