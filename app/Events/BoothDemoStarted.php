<?php

namespace App\Events;

use App\Models\Booth;
use App\Models\BoothDemo;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BoothDemoStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Booth $booth,
        public BoothDemo $demo,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("booth.{$this->booth->id}");
    }

    public function broadcastWith(): array
    {
        return [
            'demo_id' => $this->demo->id,
            'title' => $this->demo->title,
            'status' => $this->demo->status,
            'starts_at' => $this->demo->starts_at?->toISOString(),
        ];
    }
}
