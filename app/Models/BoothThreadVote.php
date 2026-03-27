<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoothThreadVote extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(BoothThread::class, 'booth_thread_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
