<?php

namespace App\Jobs;

use App\Events\SessionEnded;
use App\Models\EventSession;
use App\Models\SessionCheckIn;
use App\Services\SessionEngagementService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SessionEndedJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public EventSession $session,
    ) {}

    public function handle(SessionEngagementService $engagementService): void
    {
        // 1. Auto-checkout remaining participants
        SessionCheckIn::where('event_session_id', $this->session->id)
            ->whereNull('checked_out_at')
            ->update(['checked_out_at' => now()]);

        // 2. Compute engagement edges
        $engagementService->computeForSession($this->session);

        // 3. Broadcast session ended
        SessionEnded::dispatch($this->session);
    }
}
