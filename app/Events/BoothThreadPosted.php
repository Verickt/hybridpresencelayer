<?php

namespace App\Events;

use App\Models\Booth;
use App\Models\BoothThread;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BoothThreadPosted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Booth $booth,
        public BoothThread $thread,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("booth.{$this->booth->id}");
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->thread->id,
            'kind' => $this->thread->kind,
            'body' => $this->thread->body,
            'is_answered' => $this->thread->is_answered,
            'is_pinned' => $this->thread->is_pinned,
            'user_id' => $this->thread->user_id,
            'user_name' => $this->thread->user?->name,
            'votes_count' => $this->thread->votes()->count(),
            'replies_count' => $this->thread->replies()->count(),
        ];
    }
}
