<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionEngagementEdge extends Model
{
    protected $fillable = [
        'event_session_id',
        'user_a_id',
        'user_b_id',
        'reaction_sync_score',
        'qa_interaction_score',
    ];

    protected function casts(): array
    {
        return [
            'reaction_sync_score' => 'float',
            'qa_interaction_score' => 'float',
        ];
    }

    public function eventSession(): BelongsTo
    {
        return $this->belongsTo(EventSession::class);
    }

    public function userA(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_a_id');
    }

    public function userB(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_b_id');
    }

    public function score(): float
    {
        return ($this->reaction_sync_score * 0.6) + ($this->qa_interaction_score * 0.4);
    }
}
