<?php

namespace App\Events;

use App\Models\Booth;
use App\Models\BoothDemo;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BoothDemoEnded implements ShouldBroadcast
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
            'status' => $this->demo->status,
            'ended_at' => $this->demo->ended_at?->toISOString(),
        ];
    }
}
