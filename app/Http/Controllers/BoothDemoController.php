<?php

namespace App\Http\Controllers;

use App\Events\BoothDemoEnded;
use App\Events\BoothDemoStarted;
use App\Events\BoothThreadPosted;
use App\Models\Booth;
use App\Models\BoothDemo;
use App\Models\BoothThread;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BoothDemoController extends Controller
{
    public function store(Request $request, Event $event, Booth $booth): RedirectResponse
    {
        $this->authorizeStaff($request, $booth);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
        ]);

        if ($booth->demos()->where('status', 'live')->exists()) {
            throw ValidationException::withMessages([
                'title' => 'Es läuft bereits eine Live-Demo für diesen Stand.',
            ]);
        }

        $title = $validated['title'] ?: 'Live-Stand-Demo';

        $demo = BoothDemo::create([
            'booth_id' => $booth->id,
            'started_by_user_id' => $request->user()->id,
            'title' => $title,
            'status' => 'live',
            'starts_at' => now(),
        ]);

        $thread = BoothThread::create([
            'booth_id' => $booth->id,
            'user_id' => $request->user()->id,
            'booth_demo_id' => $demo->id,
            'kind' => 'demo_prompt',
            'body' => $title,
            'is_pinned' => true,
            'last_activity_at' => now(),
        ]);

        BoothDemoStarted::dispatch($booth, $demo);
        BoothThreadPosted::dispatch($booth, $thread->load('user'));

        return to_route('event.booths.show', [$event, $booth]);
    }

    public function end(Request $request, Event $event, Booth $booth, BoothDemo $demo): RedirectResponse
    {
        $this->authorizeStaff($request, $booth);

        $demo->update([
            'status' => 'ended',
            'ended_at' => now(),
        ]);

        $demo->threads()->update(['is_pinned' => false]);

        BoothDemoEnded::dispatch($booth, $demo->fresh());

        return to_route('event.booths.show', [$event, $booth]);
    }

    private function authorizeStaff(Request $request, Booth $booth): void
    {
        abort_unless($booth->staff()->where('user_id', $request->user()->id)->exists(), 403);
    }
}
