<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InAppNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $type,
        public string $priority,
        public string $message,
        public int $eventId,
        public array $data = [],
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'priority' => $this->priority,
            'message' => $this->message,
            'event_id' => $this->eventId,
            ...$this->data,
        ];
    }
}
