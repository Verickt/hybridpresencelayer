<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class QuickJoinController extends Controller
{
    public function show(Request $request, Event $event): Response|RedirectResponse
    {
        if ($request->user()) {
            if ($request->user()->events()->where('event_id', $event->id)->exists()) {
                return redirect()->route('event.feed', $event);
            }

            $request->user()->events()->attach($event->id, [
                'participant_type' => 'physical',
                'status' => 'available',
            ]);

            return redirect()->route('event.onboarding.type', $event);
        }

        return Inertia::render('Event/QuickJoin', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'slug' => $event->slug,
                'venue' => $event->venue,
            ],
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
        ]);

        $user->events()->attach($event->id, [
            'participant_type' => 'physical',
            'status' => 'available',
        ]);

        Auth::login($user);

        return redirect()->route('event.onboarding.type', $event);
    }
}
