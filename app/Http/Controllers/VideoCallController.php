<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\Connection;
use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VideoCallController extends Controller
{
    public function __invoke(Request $request, Event $event, Connection $connection, Call $call): Response
    {
        $userId = $request->user()->id;

        abort_unless(
            $connection->user_a_id === $userId || $connection->user_b_id === $userId,
            403
        );

        abort_unless($call->connection_id === $connection->id, 404);

        $peer = $connection->user_a_id === $userId
            ? $connection->userB
            : $connection->userA;

        return Inertia::render('Event/VideoCall', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'slug' => $event->slug,
            ],
            'connection' => [
                'id' => $connection->id,
            ],
            'call' => [
                'id' => $call->id,
                'room_id' => $call->room_id,
                'expires_at' => $call->expires_at->toISOString(),
                'extensions' => $call->extensions,
            ],
            'peer' => [
                'id' => $peer->id,
                'name' => $peer->name,
            ],
        ]);
    }
}
