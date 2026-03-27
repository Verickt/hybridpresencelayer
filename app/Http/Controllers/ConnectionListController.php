<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConnectionListController extends Controller
{
    public function __invoke(Request $request, Event $event): Response
    {
        $userId = $request->user()->id;

        $connections = Connection::where('event_id', $event->id)
            ->where(fn ($q) => $q->where('user_a_id', $userId)->orWhere('user_b_id', $userId))
            ->with(['userA:id,name,company', 'userB:id,name,company'])
            ->latest()
            ->get()
            ->map(function (Connection $c) use ($userId) {
                $other = $c->user_a_id === $userId ? $c->userB : $c->userA;

                return [
                    'connection_id' => $c->id,
                    'user' => [
                        'id' => $other->id,
                        'name' => $other->name,
                        'company' => $other->company,
                    ],
                    'context' => $c->context,
                    'is_cross_world' => $c->is_cross_world,
                    'created_at' => $c->created_at->toISOString(),
                ];
            });

        return Inertia::render('Event/Connections', [
            'event' => ['id' => $event->id, 'name' => $event->name, 'slug' => $event->slug],
            'connections' => $connections,
        ]);
    }
}
