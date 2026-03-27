<?php

namespace App\Actions;

use App\Models\Event;
use App\Models\MagicLink;
use App\Models\User;
use App\Notifications\MagicLinkNotification;

class CreateMagicLink
{
    public function handle(User $user, Event $event): array
    {
        $result = MagicLink::generate($user, $event);

        $user->notify(new MagicLinkNotification($result['token']));

        return $result;
    }
}
