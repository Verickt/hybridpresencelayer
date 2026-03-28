<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ParticipantController extends Controller
{
    public function index(Request $request, Event $event): Response
    {
        abort_unless($request->user()->id === $event->organizer_id, 403);

        $participants = $event->participants()
            ->select('users.id', 'users.name', 'users.email', 'users.created_at')
            ->get();

        return Inertia::render('Event/Participants', [
            'event' => $event->only('id', 'name', 'slug'),
            'participants' => $participants,
        ]);
    }
}
