<?php

namespace App\Services;

use App\Models\Block;
use App\Models\BoothVisit;
use App\Models\Event;
use App\Models\SessionCheckIn;
use App\Models\SessionEngagementEdge;
use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Support\Collection;

class MatchingService
{
    private float $w1 = 0.3;  // interest overlap

    private float $w2 = 0.25; // context match

    private float $w3 = 0.25; // session affinity

    private const STATUS_SCORES = [
        'available' => 1.0,
        'at_booth' => 0.5,
        'in_session' => 0.3,
        'away' => 0.1,
        'busy' => 0.0,
    ];

    public function score(User $userA, User $userB, Event $event): float
    {
        $interestOverlap = $this->interestOverlap($userA, $userB, $event);
        $contextMatch = $this->contextMatch($userA, $userB, $event);
        $sessionAffinity = $this->sessionAffinity($userA, $userB, $event);
        $availability = $this->availability($userA, $userB, $event);

        // Availability multiplies relevance — busy users (0.0) are effectively filtered out
        $relevance = ($this->w1 * $interestOverlap) + ($this->w2 * $contextMatch) + ($this->w3 * $sessionAffinity);

        return $relevance * max($availability, 0.05);
    }

    public function topMatches(User $user, Event $event, int $limit = 3): Collection
    {
        $blockedIds = Block::where('blocker_id', $user->id)
            ->where('event_id', $event->id)
            ->pluck('blocked_id');

        $blockedByIds = Block::where('blocked_id', $user->id)
            ->where('event_id', $event->id)
            ->pluck('blocker_id');

        $blockExcludeIds = $blockedIds->merge($blockedByIds)->unique();

        $participants = $event->participants()
            ->where('users.id', '!=', $user->id)
            ->where('users.is_invisible', false)
            ->whereNotIn('users.id', $blockExcludeIds)
            ->get();

        // Get recently declined/active suggestion user IDs to exclude
        $excludeIds = Suggestion::where('suggested_to_id', $user->id)
            ->where('event_id', $event->id)
            ->where(function ($q) {
                $q->where('status', 'declined')
                    ->where('updated_at', '>', now()->subHours(2));
            })
            ->orWhere(function ($q) use ($user, $event) {
                $q->where('suggested_to_id', $user->id)
                    ->where('event_id', $event->id)
                    ->where('status', 'pending');
            })
            ->pluck('suggested_user_id');

        $scored = $participants
            ->reject(fn (User $p) => $excludeIds->contains($p->id))
            ->map(fn (User $p) => [
                'user' => $p,
                'score' => $this->score($user, $p, $event),
            ]);

        $sorted = $scored->sortByDesc('score')->values();

        return $this->rerankForCrossWorld($sorted, $user, $event, $limit);
    }

    public function serendipityMatch(User $user, Event $event): ?User
    {
        $userTagIds = $user->interestTags()
            ->wherePivot('event_id', $event->id)
            ->pluck('interest_tags.id');

        return $event->participants()
            ->where('users.id', '!=', $user->id)
            ->where('users.is_invisible', false)
            ->wherePivot('status', '!=', 'busy')
            ->get()
            ->filter(function (User $candidate) use ($userTagIds, $event) {
                $candidateTagIds = $candidate->interestTags()
                    ->wherePivot('event_id', $event->id)
                    ->pluck('interest_tags.id');

                return $candidateTagIds->intersect($userTagIds)->isEmpty();
            })
            ->sortByDesc(function (User $candidate) {
                return self::STATUS_SCORES[$candidate->pivot->status ?? 'away'] ?? 0;
            })
            ->first();
    }

    private function sessionAffinity(User $userA, User $userB, Event $event): float
    {
        $userAId = min($userA->id, $userB->id);
        $userBId = max($userA->id, $userB->id);

        $edge = SessionEngagementEdge::where('user_a_id', $userAId)
            ->where('user_b_id', $userBId)
            ->whereHas('eventSession', fn ($q) => $q->where('event_id', $event->id))
            ->orderByDesc('created_at')
            ->first();

        if (! $edge) {
            return 0.0;
        }

        return $edge->score();
    }

    private function rerankForCrossWorld(Collection $sorted, User $user, Event $event, int $limit): Collection
    {
        $top = $sorted->take($limit);
        $rest = $sorted->skip($limit);

        $userType = $event->participants()->where('user_id', $user->id)->first()?->pivot->participant_type;

        if (! $userType) {
            return $top;
        }

        $hasCrossWorld = $top->contains(function ($match) use ($event, $userType) {
            $matchType = $event->participants()->where('user_id', $match['user']->id)->first()?->pivot->participant_type;

            return $matchType && $matchType !== $userType;
        });

        if ($hasCrossWorld || $top->count() < $limit) {
            return $top;
        }

        $crossWorldCandidate = $rest->first(function ($match) use ($event, $userType) {
            $matchType = $event->participants()->where('user_id', $match['user']->id)->first()?->pivot->participant_type;

            return $matchType && $matchType !== $userType;
        });

        if ($crossWorldCandidate) {
            $top = $top->take($limit - 1)->push($crossWorldCandidate);
        }

        return $top;
    }

    private function interestOverlap(User $a, User $b, Event $event): float
    {
        $tagsA = $a->interestTags()
            ->wherePivot('event_id', $event->id)
            ->pluck('interest_tags.id');

        $tagsB = $b->interestTags()
            ->wherePivot('event_id', $event->id)
            ->pluck('interest_tags.id');

        if ($tagsA->isEmpty() && $tagsB->isEmpty()) {
            return 0.0;
        }

        $shared = $tagsA->intersect($tagsB)->count();
        $max = max($tagsA->count(), $tagsB->count());

        return $max > 0 ? $shared / $max : 0.0;
    }

    private function contextMatch(User $a, User $b, Event $event): float
    {
        $score = 0.0;

        // Same session check
        $sessionA = SessionCheckIn::where('user_id', $a->id)
            ->whereNull('checked_out_at')
            ->pluck('event_session_id');

        $sessionB = SessionCheckIn::where('user_id', $b->id)
            ->whereNull('checked_out_at')
            ->pluck('event_session_id');

        if ($sessionA->intersect($sessionB)->isNotEmpty()) {
            $score += 0.5;
        }

        // Same booth check
        $boothA = BoothVisit::where('user_id', $a->id)->whereNull('left_at')->pluck('booth_id');
        $boothB = BoothVisit::where('user_id', $b->id)->whereNull('left_at')->pluck('booth_id');

        if ($boothA->intersect($boothB)->isNotEmpty()) {
            $score += 0.3;
        }

        return min($score, 1.0);
    }

    private function availability(User $a, User $b, Event $event): float
    {
        $pivotA = $a->events()->where('event_id', $event->id)->first()?->pivot;
        $pivotB = $b->events()->where('event_id', $event->id)->first()?->pivot;

        $scoreA = self::STATUS_SCORES[$pivotA?->status ?? 'away'] ?? 0.0;
        $scoreB = self::STATUS_SCORES[$pivotB?->status ?? 'away'] ?? 0.0;

        return $scoreA * $scoreB;
    }
}
