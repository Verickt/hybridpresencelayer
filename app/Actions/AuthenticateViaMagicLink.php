<?php

namespace App\Actions;

use App\Models\MagicLink;
use Illuminate\Support\Facades\Auth;

class AuthenticateViaMagicLink
{
    public function handle(string $token): ?MagicLink
    {
        $link = MagicLink::findByToken($token);

        if (! $link || ! $link->isValid()) {
            return null;
        }

        $link->consume();
        Auth::login($link->user, remember: true);

        return $link;
    }
}
