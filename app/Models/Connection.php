<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Connection extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_cross_world' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Connection $connection) {
            if ($connection->user_a_id > $connection->user_b_id) {
                [$connection->user_a_id, $connection->user_b_id] = [$connection->user_b_id, $connection->user_a_id];
            }
        });
    }

    public function userA(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_a_id');
    }

    public function userB(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_b_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
