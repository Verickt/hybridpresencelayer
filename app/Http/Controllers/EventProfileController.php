<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventProfileController extends Controller
{
    public function __invoke(Request $request, Event $event): Response
    {
        return Inertia::render('Event/Profile', [
            'event' => $event,
        ]);
    }
}
