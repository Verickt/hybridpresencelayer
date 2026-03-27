<?php

namespace App\Console\Commands;

use App\Models\Suggestion;
use Illuminate\Console\Command;

class ExpireSuggestions extends Command
{
    protected $signature = 'suggestions:expire';

    protected $description = 'Expire stale suggestions past their TTL';

    public function handle(): int
    {
        $count = Suggestion::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        $this->info("Expired {$count} suggestions.");

        return self::SUCCESS;
    }
}
