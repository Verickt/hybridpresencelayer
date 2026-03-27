<?php

namespace App\Http\Controllers\Auth;

use App\Actions\AuthenticateViaMagicLink;
use App\Actions\CreateMagicLink;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;

class MagicLinkController extends Controller
{
    public function send(Request $request, CreateMagicLink $createMagicLink)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'event_slug' => ['required', 'string'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $event = Event::where('slug', $validated['event_slug'])->firstOrFail();
        $user = User::where('email', $validated['email'])->first();

        if (! $user && $event->allow_open_registration) {
            // Validate name is required for new open-registration participants
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            $event->participants()->attach($user, [
                'participant_type' => 'remote',
                'status' => 'available',
            ]);
        }

        if (! $user) {
            // Don't reveal whether the email exists
            return response()->json(['message' => 'If your email is registered, you will receive a link.']);
        }

        $createMagicLink->handle($user, $event);

        return response()->json(['message' => 'If your email is registered, you will receive a link.']);
    }

    public function authenticate(string $token, AuthenticateViaMagicLink $authenticate)
    {
        $link = $authenticate->handle($token);

        if (! $link) {
            return redirect()->route('login')
                ->with('error', 'This link is invalid or has expired. Please request a new one.');
        }

        return redirect()->route('dashboard');
    }
}
