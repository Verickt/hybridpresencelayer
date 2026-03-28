<?php

namespace App\Events;

use App\Models\EventSession;
use App\Models\SessionQuestion;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionQuestionPinned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public EventSession $session,
        public SessionQuestion $question,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("session.{$this->session->id}");
    }

    public function broadcastWith(): array
    {
        return [
            'question_id' => $this->question->id,
            'is_pinned' => $this->question->is_pinned,
        ];
    }
}
