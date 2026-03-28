<?php

namespace App\Console\Commands;

use App\Jobs\SessionEndedJob;
use App\Models\EventSession;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('matching:post-session')]
#[Description('Generate suggestions for participants of recently ended sessions')]
class TriggerPostSessionMatching extends Command
{
    public function handle(): int
    {
        $sessions = EventSession::where('ends_at', '>=', now()->subMinutes(15))
            ->where('ends_at', '<=', now())
            ->whereDoesntHave('engagementEdges')
            ->get();

        $count = 0;

        foreach ($sessions as $session) {
            SessionEndedJob::dispatch($session);
            $count++;
        }

        $this->info("Dispatched SessionEndedJob for {$count} sessions.");

        return self::SUCCESS;
    }
}
