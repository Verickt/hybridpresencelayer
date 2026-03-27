<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\SharedInterest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SharedInterestController extends Controller
{
    public function store(Request $request, Event $event): JsonResponse
    {
        $request->validate([
            'topic' => ['required', 'string', 'max:255'],
            'event_session_id' => ['nullable', 'exists:event_sessions,id'],
            'booth_id' => ['nullable', 'exists:booths,id'],
        ]);

        $interest = SharedInterest::create([
            'user_id' => $request->user()->id,
            'event_id' => $event->id,
            'event_session_id' => $request->input('event_session_id'),
            'booth_id' => $request->input('booth_id'),
            'topic' => $request->input('topic'),
            'expires_at' => now()->addMinutes(30),
        ]);

        return response()->json([
            'message' => 'Interest shared',
            'interest' => $interest,
        ]);
    }

    public function index(Request $request, Event $event): JsonResponse
    {
        $interests = SharedInterest::where('event_id', $event->id)
            ->active()
            ->with('user:id,name,company,role_title')
            ->latest()
            ->get();

        return response()->json(['interests' => $interests]);
    }
}
