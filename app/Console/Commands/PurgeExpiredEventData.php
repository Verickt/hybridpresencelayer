<?php

namespace App\Console\Commands;

use App\Models\Event;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurgeExpiredEventData extends Command
{
    protected $signature = 'events:purge-expired';

    protected $description = 'Delete participant data for events that ended more than 30 days ago';

    public function handle(): int
    {
        $events = Event::where('ends_at', '<', now()->subDays(30))->get();

        if ($events->isEmpty()) {
            $this->info('No expired events found.');

            return self::SUCCESS;
        }

        foreach ($events as $event) {
            $this->purgeEventData($event);
        }

        $this->info("Purged data for {$events->count()} expired event(s).");

        return self::SUCCESS;
    }

    private function purgeEventData(Event $event): void
    {
        $eventId = $event->id;
        $sessionIds = $event->sessions()->pluck('id');
        $boothIds = $event->booths()->pluck('id');
        $connectionIds = DB::table('connections')->where('event_id', $eventId)->pluck('id');

        $counts = [];

        // Messages via connections
        $counts['messages'] = DB::table('messages')->whereIn('connection_id', $connectionIds)->delete();

        // Connections
        $counts['connections'] = DB::table('connections')->where('event_id', $eventId)->delete();

        // Pings
        $counts['pings'] = DB::table('pings')->where('event_id', $eventId)->delete();

        // Suggestions
        $counts['suggestions'] = DB::table('suggestions')->where('event_id', $eventId)->delete();

        // Session check-ins, reactions, questions (and votes)
        $questionIds = DB::table('session_questions')->whereIn('event_session_id', $sessionIds)->pluck('id');
        $counts['session_question_votes'] = DB::table('session_question_votes')->whereIn('session_question_id', $questionIds)->delete();
        $counts['session_questions'] = DB::table('session_questions')->whereIn('event_session_id', $sessionIds)->delete();
        $counts['session_reactions'] = DB::table('session_reactions')->whereIn('event_session_id', $sessionIds)->delete();
        $counts['session_check_ins'] = DB::table('session_check_ins')->whereIn('event_session_id', $sessionIds)->delete();

        // Booth visits, threads, replies, votes, demos
        $threadIds = DB::table('booth_threads')->whereIn('booth_id', $boothIds)->pluck('id');
        $counts['booth_thread_votes'] = DB::table('booth_thread_votes')->whereIn('booth_thread_id', $threadIds)->delete();
        $counts['booth_thread_replies'] = DB::table('booth_thread_replies')->whereIn('booth_thread_id', $threadIds)->delete();
        $counts['booth_threads'] = DB::table('booth_threads')->whereIn('booth_id', $boothIds)->delete();
        $counts['booth_visits'] = DB::table('booth_visits')->whereIn('booth_id', $boothIds)->delete();
        $counts['booth_demos'] = DB::table('booth_demos')->whereIn('booth_id', $boothIds)->delete();

        // Magic links
        $counts['magic_links'] = DB::table('magic_links')->where('event_id', $eventId)->delete();

        // Icebreaker questions
        $counts['icebreaker_questions'] = DB::table('icebreaker_questions')->where('event_id', $eventId)->delete();

        // Notifications for participants of this event
        $participantIds = DB::table('event_user')->where('event_id', $eventId)->pluck('user_id');
        $counts['notifications'] = DB::table('notifications')
            ->where('notifiable_type', 'App\\Models\\User')
            ->whereIn('notifiable_id', $participantIds)
            ->delete();

        // Participants pivot
        $counts['participants'] = DB::table('event_user')->where('event_id', $eventId)->delete();

        // Interest tag pivots
        DB::table('event_interest_tag')->where('event_id', $eventId)->delete();
        DB::table('user_interest_tag')->where('event_id', $eventId)->delete();

        $summary = collect($counts)->filter()->map(fn ($count, $table) => "{$table}: {$count}")->implode(', ');

        $message = "Purged event [{$event->name}] (ID: {$eventId}): {$summary}";
        $this->line($message);
        Log::info($message);
    }
}
