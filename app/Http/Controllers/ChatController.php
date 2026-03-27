<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function __invoke(Request $request, Event $event, Connection $connection): Response
    {
        $userId = $request->user()->id;

        abort_unless(
            $connection->user_a_id === $userId || $connection->user_b_id === $userId,
            403
        );

        $peer = $connection->user_a_id === $userId
            ? $connection->userB
            : $connection->userA;

        return Inertia::render('Event/Chat', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'slug' => $event->slug,
            ],
            'connection' => [
                'id' => $connection->id,
            ],
            'peer' => [
                'id' => $peer->id,
                'name' => $peer->name,
                'company' => $peer->company,
            ],
        ]);
    }
}
