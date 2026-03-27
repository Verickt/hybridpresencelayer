<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoothVisit extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_anonymous' => 'boolean',
            'entered_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booth(): BelongsTo
    {
        return $this->belongsTo(Booth::class);
    }

    public function fromSession(): BelongsTo
    {
        return $this->belongsTo(EventSession::class, 'from_session_id');
    }

    public function durationInMinutes(): int
    {
        $end = $this->left_at ?? now();

        return (int) $this->entered_at->diffInMinutes($end);
    }
}
