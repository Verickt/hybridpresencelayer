<?php

namespace App\Events;

use App\Models\Booth;
use App\Models\BoothThread;
use App\Models\BoothThreadReply;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BoothThreadReplyPosted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Booth $booth,
        public BoothThread $thread,
        public BoothThreadReply $reply,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("booth.{$this->booth->id}");
    }

    public function broadcastWith(): array
    {
        return [
            'thread_id' => $this->thread->id,
            'reply_id' => $this->reply->id,
            'body' => $this->reply->body,
            'user_id' => $this->reply->user_id,
            'user_name' => $this->reply->user?->name,
            'is_staff_answer' => $this->reply->is_staff_answer,
            'replies_count' => $this->thread->replies()->count(),
        ];
    }
}
