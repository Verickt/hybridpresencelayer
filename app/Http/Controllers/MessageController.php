<?php

namespace App\Http\Controllers;

use App\Events\NewMessage;
use App\Models\Connection;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request, Connection $connection): JsonResponse
    {
        $this->authorizeConnection($request, $connection);

        $messages = $connection->messages()
            ->with('sender:id,name')
            ->orderBy('created_at')
            ->get()
            ->map(fn (Message $m) => [
                'id' => $m->id,
                'sender_id' => $m->sender_id,
                'sender_name' => $m->sender->name,
                'body' => $m->body,
                'created_at' => $m->created_at->toISOString(),
            ]);

        return response()->json(['data' => $messages]);
    }

    public function store(Request $request, Connection $connection): JsonResponse
    {
        $this->authorizeConnection($request, $connection);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:500'],
        ]);

        $message = Message::create([
            'connection_id' => $connection->id,
            'sender_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        NewMessage::dispatch($message);

        return response()->json(['message' => 'Sent']);
    }

    private function authorizeConnection(Request $request, Connection $connection): void
    {
        $userId = $request->user()->id;

        abort_unless(
            $connection->user_a_id === $userId || $connection->user_b_id === $userId,
            403
        );
    }
}
