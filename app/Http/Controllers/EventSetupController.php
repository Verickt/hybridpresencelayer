<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;

class EventSetupController extends Controller
{
    public function store(Request $request)
    {
        abort_unless($request->user()->is_organizer, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'venue' => ['nullable', 'string'],
            'streaming_url' => ['nullable', 'url'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'allow_open_registration' => ['boolean'],
        ]);

        $event = Event::create([
            ...$validated,
            'organizer_id' => $request->user()->id,
        ]);

        return redirect()->route('event.dashboard', $event);
    }

    public function update(Request $request, Event $event)
    {
        abort_unless($request->user()->id === $event->organizer_id, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'venue' => ['nullable', 'string'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'allow_open_registration' => ['boolean'],
        ]);

        $event->update($validated);

        return redirect()->route('event.dashboard', $event);
    }

    public function importAttendees(Request $request, Event $event)
    {
        abort_unless($request->user()->id === $event->organizer_id, 403);

        $validated = $request->validate([
            'attendees' => ['required', 'array'],
            'attendees.*.name' => ['required', 'string'],
            'attendees.*.email' => ['required', 'email'],
            'attendees.*.participant_type' => ['required', 'in:physical,remote'],
            'attendees.*.company' => ['nullable', 'string'],
            'attendees.*.role_title' => ['nullable', 'string'],
        ]);

        foreach ($validated['attendees'] as $attendee) {
            $user = User::firstOrCreate(
                ['email' => $attendee['email']],
                [
                    'name' => $attendee['name'],
                    'company' => $attendee['company'] ?? null,
                    'role_title' => $attendee['role_title'] ?? null,
                    'password' => bcrypt(str()->random(32)),
                ]
            );

            if (! $event->participants()->where('user_id', $user->id)->exists()) {
                $event->participants()->attach($user, [
                    'participant_type' => $attendee['participant_type'],
                    'status' => 'available',
                ]);
            }
        }

        return response()->json(['message' => 'Attendees imported', 'count' => count($validated['attendees'])]);
    }
}
