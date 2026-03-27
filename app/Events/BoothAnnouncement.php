<?php

namespace App\Events;

use App\Models\Booth;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BoothAnnouncement implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Booth $booth,
        public string $message,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("event.{$this->booth->event_id}.presence");
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'booth_id' => $this->booth->id,
            'booth_name' => $this->booth->name,
            'message' => $this->message,
        ];
    }
}
