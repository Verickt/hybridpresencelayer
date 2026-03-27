<?php

namespace App\Events;

use App\Models\Event;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PresenceStateChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Event $event,
        public User $user,
        public string $status,
        public ?string $contextBadge = null,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("event.{$this->event->id}.presence");
    }

    /**
     * @return array{user_id: int, name: string, status: string, context_badge: ?string, participant_type: ?string, occurred_at: string}
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'name' => $this->user->name,
            'status' => $this->status,
            'context_badge' => $this->contextBadge,
            'participant_type' => $this->user->events()
                ->where('event_id', $this->event->id)
                ->first()?->pivot?->participant_type,
            'occurred_at' => now()->toISOString(),
        ];
    }
}
