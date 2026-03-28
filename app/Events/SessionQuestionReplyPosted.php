<?php

namespace App\Events;

use App\Models\EventSession;
use App\Models\SessionQuestionReply;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionQuestionReplyPosted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public EventSession $session,
        public SessionQuestionReply $reply,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("session.{$this->session->id}");
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->reply->id,
            'session_question_id' => $this->reply->session_question_id,
            'body' => $this->reply->body,
            'user_id' => $this->reply->user_id,
            'user_name' => $this->reply->user->name,
            'votes_count' => 0,
        ];
    }
}
