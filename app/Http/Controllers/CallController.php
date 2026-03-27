<?php

namespace App\Http\Controllers;

use App\Events\CallInitiated;
use App\Models\Call;
use App\Models\Connection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CallController extends Controller
{
    public function start(Request $request, Connection $connection): JsonResponse
    {
        $userId = $request->user()->id;
        abort_unless($connection->user_a_id === $userId || $connection->user_b_id === $userId, 403);

        $receiverId = $connection->user_a_id === $userId
            ? $connection->user_b_id
            : $connection->user_a_id;

        $call = Call::create([
            'connection_id' => $connection->id,
            'initiator_id' => $userId,
            'room_id' => Str::uuid()->toString(),
            'started_at' => now(),
            'expires_at' => now()->addMinutes(3),
        ]);

        CallInitiated::dispatch($connection, $call, $receiverId);

        return response()->json([
            'call_id' => $call->id,
            'room_id' => $call->room_id,
            'expires_at' => $call->expires_at->toISOString(),
        ]);
    }

    public function extend(Request $request, Connection $connection, Call $call): JsonResponse
    {
        $userId = $request->user()->id;
        abort_unless($connection->user_a_id === $userId || $connection->user_b_id === $userId, 403);

        if (! $call->canExtend()) {
            return response()->json(['message' => 'Maximum extensions reached'], 422);
        }

        $call->update([
            'expires_at' => $call->expires_at->addMinutes(3),
            'extensions' => $call->extensions + 1,
        ]);

        return response()->json([
            'expires_at' => $call->fresh()->expires_at->toISOString(),
            'extensions' => $call->extensions,
        ]);
    }

    public function end(Request $request, Connection $connection, Call $call): JsonResponse
    {
        $userId = $request->user()->id;
        abort_unless($connection->user_a_id === $userId || $connection->user_b_id === $userId, 403);

        $call->update(['ended_at' => now()]);

        return response()->json(['message' => 'Call ended']);
    }
}
