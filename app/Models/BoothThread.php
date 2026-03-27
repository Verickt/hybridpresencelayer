<?php

namespace App\Models;

use Database\Factories\BoothThreadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoothThread extends Model
{
    /** @use HasFactory<BoothThreadFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_answered' => 'boolean',
            'is_pinned' => 'boolean',
            'last_activity_at' => 'datetime',
            'follow_up_requested_at' => 'datetime',
        ];
    }

    public function booth(): BelongsTo
    {
        return $this->belongsTo(Booth::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function boothDemo(): BelongsTo
    {
        return $this->belongsTo(BoothDemo::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(BoothThreadReply::class)->latest();
    }

    public function votes(): HasMany
    {
        return $this->hasMany(BoothThreadVote::class);
    }
}
