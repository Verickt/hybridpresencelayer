<?php

namespace App\Events;

use App\Models\Connection;
use App\Models\Event;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MutualMatchCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Event $event,
        public Connection $userConnection,
        public User $userA,
        public User $userB,
    ) {}

    /** @return array<PrivateChannel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->userA->id}.notifications"),
            new PrivateChannel("user.{$this->userB->id}.notifications"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'connection_id' => $this->userConnection->id,
            'user_a' => ['id' => $this->userA->id, 'name' => $this->userA->name],
            'user_b' => ['id' => $this->userB->id, 'name' => $this->userB->name],
            'event_id' => $this->event->id,
        ];
    }
}
