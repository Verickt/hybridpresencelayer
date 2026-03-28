<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventLandingController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        if ($request->user()) {
            $user = $request->user();
            $event = $user->events()->latest('event_user.created_at')->first()
                ?? $user->organizedEvents()->latest()->first();

            if (! $event) {
                return Inertia::render('JoinEvent', ['event' => null]);
            }

            if ($user->id === $event->organizer_id) {
                return redirect()->route('event.dashboard', $event);
            }

            return redirect()->route('event.feed', $event);
        }

        $event = Event::first();

        return Inertia::render('JoinEvent', [
            'event' => $event ? [
                'name' => $event->name,
                'slug' => $event->slug,
                'venue' => $event->venue,
                'starts_at' => $event->starts_at?->toISOString(),
                'ends_at' => $event->ends_at?->toISOString(),
            ] : null,
        ]);
    }
}
