<?php

namespace App\Services;

use App\Models\EventSession;
use App\Models\SessionCheckIn;
use App\Models\SessionEngagementEdge;
use App\Models\SessionQuestion;
use App\Models\SessionQuestionReply;
use App\Models\SessionReaction;
use Illuminate\Support\Collection;

class SessionEngagementService
{
    private const WINDOW_SECONDS = 30;

    public function computeForSession(EventSession $session): void
    {
        $attendeeIds = SessionCheckIn::where('event_session_id', $session->id)
            ->pluck('user_id')
            ->unique()
            ->values();

        if ($attendeeIds->count() < 2) {
            return;
        }

        $reactionFingerprints = $this->buildReactionFingerprints($session, $attendeeIds);
        $qaEdges = $this->buildQaInteractionEdges($session, $attendeeIds);

        $attendeeArray = $attendeeIds->toArray();
        $pairCount = count($attendeeArray);

        for ($i = 0; $i < $pairCount; $i++) {
            for ($j = $i + 1; $j < $pairCount; $j++) {
                $userAId = min($attendeeArray[$i], $attendeeArray[$j]);
                $userBId = max($attendeeArray[$i], $attendeeArray[$j]);

                $reactionScore = $this->computeReactionSync(
                    $reactionFingerprints->get($userAId, collect()),
                    $reactionFingerprints->get($userBId, collect()),
                );

                $qaScore = $qaEdges->get("{$userAId}:{$userBId}", 0.0);

                if ($reactionScore === 0.0 && $qaScore === 0.0) {
                    continue;
                }

                SessionEngagementEdge::updateOrCreate(
                    [
                        'event_session_id' => $session->id,
                        'user_a_id' => $userAId,
                        'user_b_id' => $userBId,
                    ],
                    [
                        'reaction_sync_score' => $reactionScore,
                        'qa_interaction_score' => $qaScore,
                    ]
                );
            }
        }
    }

    private function buildReactionFingerprints(EventSession $session, Collection $attendeeIds): Collection
    {
        $reactions = SessionReaction::where('event_session_id', $session->id)
            ->whereIn('user_id', $attendeeIds)
            ->orderBy('created_at')
            ->get(['user_id', 'created_at']);

        if ($reactions->isEmpty()) {
            return collect();
        }

        $sessionStart = $session->starts_at;

        return $reactions->groupBy('user_id')->map(function (Collection $userReactions) use ($sessionStart) {
            return $userReactions->map(function ($reaction) use ($sessionStart) {
                return (int) floor($sessionStart->diffInSeconds($reaction->created_at) / self::WINDOW_SECONDS);
            })->unique()->values();
        });
    }

    private function computeReactionSync(Collection $windowsA, Collection $windowsB): float
    {
        if ($windowsA->isEmpty() || $windowsB->isEmpty()) {
            return 0.0;
        }

        $shared = $windowsA->intersect($windowsB)->count();
        $total = max($windowsA->count(), $windowsB->count());

        return $total > 0 ? $shared / $total : 0.0;
    }

    private function buildQaInteractionEdges(EventSession $session, Collection $attendeeIds): Collection
    {
        $edges = collect();

        $questions = SessionQuestion::where('event_session_id', $session->id)
            ->whereIn('user_id', $attendeeIds)
            ->with(['replies' => fn ($q) => $q->whereIn('user_id', $attendeeIds)])
            ->with(['votes' => fn ($q) => $q->whereIn('user_id', $attendeeIds)])
            ->get();

        foreach ($questions as $question) {
            foreach ($question->replies as $reply) {
                if ($reply->user_id === $question->user_id) {
                    continue;
                }
                $key = $this->pairKey($question->user_id, $reply->user_id);
                $edges[$key] = ($edges[$key] ?? 0) + 0.4;
            }

            $voterIds = $question->votes->pluck('user_id')->unique();
            $voterArray = $voterIds->toArray();
            for ($i = 0; $i < count($voterArray); $i++) {
                for ($j = $i + 1; $j < count($voterArray); $j++) {
                    $key = $this->pairKey($voterArray[$i], $voterArray[$j]);
                    $edges[$key] = ($edges[$key] ?? 0) + 0.3;
                }
            }
        }

        $replies = SessionQuestionReply::whereIn('session_question_id', $questions->pluck('id'))
            ->whereIn('user_id', $attendeeIds)
            ->with(['votes' => fn ($q) => $q->whereIn('user_id', $attendeeIds)])
            ->get();

        foreach ($replies as $reply) {
            foreach ($reply->votes as $vote) {
                if ($vote->user_id === $reply->user_id) {
                    continue;
                }
                $key = $this->pairKey($reply->user_id, $vote->user_id);
                $edges[$key] = ($edges[$key] ?? 0) + 0.3;
            }
        }

        return $edges->map(fn ($score) => min($score, 1.0));
    }

    private function pairKey(int $idA, int $idB): string
    {
        return min($idA, $idB).':'.max($idA, $idB);
    }
}
