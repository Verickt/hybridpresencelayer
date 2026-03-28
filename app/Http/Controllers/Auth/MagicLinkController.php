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
            return back()->with('magic_link_sent', true);
        }

        $createMagicLink->handle($user, $event);

        return back()->with('magic_link_sent', true);
    }

    public function authenticate(string $token, AuthenticateViaMagicLink $authenticate)
    {
        $link = $authenticate->handle($token);

        if (! $link) {
            return redirect()->route('login')
                ->with('error', 'This link is invalid or has expired. Please request a new one.');
        }

        $event = $link->event;

        // Organizers go to dashboard
        if ($link->user->id === $event->organizer_id) {
            return redirect()->route('event.dashboard', $event);
        }

        // New participants go through onboarding, returning participants go to feed
        $hasInterestTags = $link->user->interestTags()
            ->wherePivot('event_id', $event->id)
            ->exists();

        if (! $hasInterestTags) {
            return redirect()->route('event.onboarding.type', $event);
        }

        return redirect()->route('event.feed', $event);
    }
}
