<?php

namespace App\Events;

use App\Models\Event;
use App\Models\Ping;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PingReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Event $event,
        public Ping $ping,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("user.{$this->ping->receiver_id}.notifications");
    }

    public function broadcastWith(): array
    {
        return [
            'ping_id' => $this->ping->id,
            'sender' => [
                'id' => $this->ping->sender->id,
                'name' => $this->ping->sender->name,
            ],
            'event_id' => $this->event->id,
        ];
    }
}
