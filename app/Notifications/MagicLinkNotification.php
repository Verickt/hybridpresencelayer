<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MagicLinkNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $rawToken,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('magic-link.authenticate', ['token' => $this->rawToken]);

        return (new MailMessage)
            ->subject('Your event access link')
            ->line('Click the button below to join the event.')
            ->action('Join Event', $url)
            ->line('This link expires in 48 hours and can only be used once.');
    }
}
