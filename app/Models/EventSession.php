<?php

namespace App\Models;

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

    public function isLive(): bool
    {
        return now()->between($this->starts_at, $this->ends_at);
    }
}
