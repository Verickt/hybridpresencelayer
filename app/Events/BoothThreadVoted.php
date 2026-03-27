<?php

namespace App\Events;

use App\Models\Booth;
use App\Models\BoothThread;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BoothThreadVoted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Booth $booth,
        public BoothThread $thread,
        public int $votesCount,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("booth.{$this->booth->id}");
    }

    public function broadcastWith(): array
    {
        return [
            'thread_id' => $this->thread->id,
            'votes_count' => $this->votesCount,
        ];
    }
}
