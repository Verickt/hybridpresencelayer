<?php

namespace App\Services;

use App\Models\Connection;
use App\Models\Event;
use App\Models\Ping;
use App\Models\Suggestion;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function overview(Event $event): array
    {
        return Cache::remember(
            "event.{$event->id}.dashboard.overview",
            30,
            fn () => $this->computeOverview($event)
        );
    }

    private function computeOverview(Event $event): array
    {
        $participants = $event->participants()->get();
        $physicalCount = $participants->filter(fn ($p) => $p->pivot->participant_type === 'physical')->count();
        $remoteCount = $participants->filter(fn ($p) => $p->pivot->participant_type === 'remote')->count();
        $totalActive = $physicalCount + $remoteCount;

        $totalConnections = Connection::where('event_id', $event->id)->count();
        $crossWorldConnections = Connection::where('event_id', $event->id)->where('is_cross_world', true)->count();
        $crossPollinationRate = $totalConnections > 0
            ? round(($crossWorldConnections / $totalConnections) * 100, 1)
            : 0.0;

        $interactedUserIds = Ping::where('event_id', $event->id)
            ->pluck('sender_id')
            ->merge(Ping::where('event_id', $event->id)->pluck('receiver_id'))
            ->unique();
        $interactionRate = $totalActive > 0
            ? round(($interactedUserIds->count() / $totalActive) * 100, 1)
            : 0.0;

        $totalSuggestions = Suggestion::where('event_id', $event->id)->count();
        $acceptedSuggestions = Suggestion::where('event_id', $event->id)->where('status', 'accepted')->count();
        $matchAcceptanceRate = $totalSuggestions > 0
            ? round(($acceptedSuggestions / $totalSuggestions) * 100, 1)
            : 0.0;

        $networkingDensity = $totalActive > 1
            ? round((2 * $totalConnections) / ($totalActive * ($totalActive - 1)) * 100, 2)
            : 0.0;

        return [
            'total_active' => $totalActive,
            'physical_count' => $physicalCount,
            'remote_count' => $remoteCount,
            'total_connections' => $totalConnections,
            'cross_pollination_rate' => $crossPollinationRate,
            'interaction_rate' => $interactionRate,
            'match_acceptance_rate' => $matchAcceptanceRate,
            'networking_density' => $networkingDensity,
        ];
    }

    public function sessionAnalytics(Event $event): array
    {
        return $event->sessions()
            ->withCount(['checkIns', 'reactions', 'questions'])
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'title' => $s->title,
                'check_ins_count' => $s->check_ins_count,
                'reactions_count' => $s->reactions_count,
                'questions_count' => $s->questions_count,
            ])
            ->toArray();
    }

    public function boothPerformance(Event $event): array
    {
        return $event->booths()
            ->withCount(['visits' => fn ($q) => $q->where('is_anonymous', false)])
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'company' => $b->company,
                'visitor_count' => $b->visits_count,
            ])
            ->sortByDesc('visitor_count')
            ->values()
            ->toArray();
    }
}
