<?php

namespace App\Events;

use App\Models\EventSession;
use App\Models\SessionReaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionReactionSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public EventSession $session,
        public SessionReaction $reaction,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("session.{$this->session->id}");
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->reaction->type,
            'user_id' => $this->reaction->user_id,
        ];
    }
}
