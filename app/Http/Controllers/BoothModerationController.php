<?php

namespace App\Http\Controllers;

use App\Models\Booth;
use App\Models\BoothThread;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BoothModerationController extends Controller
{
    public function answer(Request $request, Event $event, Booth $booth, BoothThread $thread): RedirectResponse
    {
        $this->authorizeStaff($request, $booth);

        $thread->update([
            'is_answered' => true,
            'last_activity_at' => now(),
        ]);

        return to_route('event.booths.show', [$event, $booth]);
    }

    public function pin(Request $request, Event $event, Booth $booth, BoothThread $thread): RedirectResponse
    {
        $this->authorizeStaff($request, $booth);

        abort_unless($thread->kind === 'question', 403);

        $shouldPin = ! $thread->is_pinned;

        $booth->threads()
            ->where('kind', 'question')
            ->update(['is_pinned' => false]);

        $thread->update([
            'is_pinned' => $shouldPin,
            'last_activity_at' => now(),
        ]);

        return to_route('event.booths.show', [$event, $booth]);
    }

    private function authorizeStaff(Request $request, Booth $booth): void
    {
        abort_unless($booth->staff()->where('user_id', $request->user()->id)->exists(), 403);
    }
}
