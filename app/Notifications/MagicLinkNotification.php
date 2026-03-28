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
            ->subject('Ihr Zugangslink zur Veranstaltung')
            ->line('Klicken Sie auf die Schaltfläche unten, um der Veranstaltung beizutreten.')
            ->action('Veranstaltung beitreten', $url)
            ->line('Dieser Link ist 48 Stunden gültig und kann nur einmal verwendet werden.');
    }
}
