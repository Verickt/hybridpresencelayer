<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventSession extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'qa_enabled' => 'boolean',
            'reactions_enabled' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function speakerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'speaker_user_id');
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(SessionCheckIn::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(SessionReaction::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(SessionQuestion::class);
    }

    public function engagementEdges(): HasMany
    {
        return $this->hasMany(SessionEngagementEdge::class);
    }

    public function isLive(): bool
    {
        return now()->between($this->starts_at, $this->ends_at);
    }

    public function isJoinable(): bool
    {
        return now()->between(
            $this->joinWindowStartsAt(),
            $this->joinWindowEndsAt(),
        );
    }

    public function canInteract(): bool
    {
        return $this->isLive();
    }

    public function hasActiveCheckInFor(User $user): bool
    {
        return $this->checkIns()
            ->where('user_id', $user->id)
            ->whereNull('checked_out_at')
            ->exists();
    }

    public function joinWindowStartsAt(): CarbonInterface
    {
        return $this->starts_at->copy()->subMinutes(10);
    }

    public function joinWindowEndsAt(): CarbonInterface
    {
        return $this->ends_at->copy()->addMinutes(15);
    }
}
