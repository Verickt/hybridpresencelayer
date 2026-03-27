<?php

namespace App\Events;

use App\Models\Call;
use App\Models\Connection;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallInitiated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Connection $userConnection,
        public Call $call,
        public int $receiverId,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("user.{$this->receiverId}.notifications");
    }

    public function broadcastWith(): array
    {
        return [
            'call_id' => $this->call->id,
            'room_id' => $this->call->room_id,
            'connection_id' => $this->userConnection->id,
            'initiator_name' => $this->call->initiator->name,
            'expires_at' => $this->call->expires_at->toISOString(),
        ];
    }
}
