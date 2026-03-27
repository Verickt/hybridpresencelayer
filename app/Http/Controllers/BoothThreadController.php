<?php

namespace App\Http\Controllers;

use App\Events\BoothThreadPosted;
use App\Events\BoothThreadReplyPosted;
use App\Events\BoothThreadVoted;
use App\Models\Booth;
use App\Models\BoothThread;
use App\Models\BoothThreadVote;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BoothThreadController extends Controller
{
    public function store(Request $request, Event $event, Booth $booth): RedirectResponse
    {
        $this->authorizeParticipant($request, $event);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:500'],
        ]);

        $thread = BoothThread::create([
            'booth_id' => $booth->id,
            'user_id' => $request->user()->id,
            'kind' => 'question',
            'body' => $validated['body'],
            'last_activity_at' => now(),
        ]);

        BoothThreadPosted::dispatch($booth, $thread->load('user'));

        return to_route('event.booths.show', [$event, $booth]);
    }

    public function reply(Request $request, Event $event, Booth $booth, BoothThread $thread): RedirectResponse
    {
        $this->authorizeStaff($request, $booth);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $reply = $thread->replies()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
            'is_staff_answer' => true,
        ]);

        $thread->update(['last_activity_at' => now()]);

        BoothThreadReplyPosted::dispatch($booth, $thread, $reply->load('user'));

        return to_route('event.booths.show', [$event, $booth]);
    }

    public function vote(Request $request, Event $event, Booth $booth, BoothThread $thread): RedirectResponse
    {
        $this->authorizeParticipant($request, $event);

        $exists = BoothThreadVote::where('booth_thread_id', $thread->id)
            ->where('user_id', $request->user()->id)
            ->exists();

        if (! $exists) {
            BoothThreadVote::create([
                'booth_thread_id' => $thread->id,
                'user_id' => $request->user()->id,
            ]);

            $thread->update(['last_activity_at' => now()]);

            BoothThreadVoted::dispatch($booth, $thread, $thread->votes()->count());
        }

        return to_route('event.booths.show', [$event, $booth]);
    }

    public function followUp(Request $request, Event $event, Booth $booth, BoothThread $thread): RedirectResponse
    {
        $this->authorizeParticipant($request, $event);

        abort_unless($thread->user_id === $request->user()->id, 403);

        if ($thread->follow_up_requested_at === null) {
            $thread->update([
                'follow_up_requested_at' => now(),
                'last_activity_at' => now(),
            ]);
        }

        return to_route('event.booths.show', [$event, $booth]);
    }

    private function authorizeParticipant(Request $request, Event $event): void
    {
        abort_unless(
            $request->user()->events()->where('event_id', $event->id)->exists(),
            403
        );
    }

    private function authorizeStaff(Request $request, Booth $booth): void
    {
        abort_unless($booth->staff()->where('user_id', $request->user()->id)->exists(), 403);
    }
}
