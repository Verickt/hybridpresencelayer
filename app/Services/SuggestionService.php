<?php

namespace App\Services;

use App\Models\Connection;
use App\Models\Event;
use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Support\Collection;

class SuggestionService
{
    private const MAX_ACTIVE = 3;

    public function __construct(
        private MatchingService $matchingService,
    ) {}

    public function generateForUser(User $user, Event $event): Collection
    {
        $activeCount = Suggestion::forUser($user)
            ->where('event_id', $event->id)
            ->active()
            ->count();

        $slotsAvailable = self::MAX_ACTIVE - $activeCount;

        if ($slotsAvailable <= 0) {
            return collect();
        }

        // Get IDs of already-connected users
        $connectedIds = Connection::where(function ($q) use ($user) {
            $q->where('user_a_id', $user->id)
                ->orWhere('user_b_id', $user->id);
        })
            ->where('event_id', $event->id)
            ->get()
            ->map(fn (Connection $c) => $c->user_a_id === $user->id ? $c->user_b_id : $c->user_a_id);

        $matches = $this->matchingService->topMatches($user, $event, $slotsAvailable);

        return $matches
            ->reject(fn (array $match) => $connectedIds->contains($match['user']->id))
            ->map(fn (array $match) => Suggestion::create([
                'suggested_to_id' => $user->id,
                'suggested_user_id' => $match['user']->id,
                'event_id' => $event->id,
                'score' => $match['score'],
                'reason' => $this->buildReason($user, $match['user'], $event),
                'trigger' => 'interest_overlap',
                'expires_at' => now()->addMinutes(15),
            ]));
    }

    public function decline(Suggestion $suggestion): void
    {
        $suggestion->update(['status' => 'declined']);
    }

    public function accept(Suggestion $suggestion): void
    {
        $suggestion->update(['status' => 'accepted']);
    }

    private function buildReason(User $a, User $b, Event $event): string
    {
        $tagsA = $a->interestTags()->wherePivot('event_id', $event->id)->pluck('name');
        $tagsB = $b->interestTags()->wherePivot('event_id', $event->id)->pluck('name');
        $shared = $tagsA->intersect($tagsB);

        if ($shared->isNotEmpty()) {
            return "You both tagged: {$shared->implode(', ')}";
        }

        return 'Suggested based on availability and context';
    }
}
