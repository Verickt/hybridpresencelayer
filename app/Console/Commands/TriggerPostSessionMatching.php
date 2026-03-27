<?php

namespace App\Console\Commands;

use App\Models\EventSession;
use App\Services\SuggestionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('matching:post-session')]
#[Description('Generate suggestions for participants of recently ended sessions')]
class TriggerPostSessionMatching extends Command
{
    public function handle(SuggestionService $suggestionService): int
    {
        $sessions = EventSession::where('ends_at', '>=', now()->subMinutes(15))
            ->where('ends_at', '<=', now())
            ->with(['checkIns' => fn ($q) => $q->whereNull('checked_out_at'), 'event'])
            ->get();

        $count = 0;

        foreach ($sessions as $session) {
            foreach ($session->checkIns as $checkIn) {
                $suggestions = $suggestionService->generateForUser($checkIn->user, $session->event);
                $count += $suggestions->count();
            }
        }

        $this->info("Generated {$count} suggestions from {$sessions->count()} sessions.");

        return self::SUCCESS;
    }
}
