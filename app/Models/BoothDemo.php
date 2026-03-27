<?php

namespace App\Models;

use Database\Factories\BoothDemoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BoothDemo extends Model
{
    /** @use HasFactory<BoothDemoFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function booth(): BelongsTo
    {
        return $this->belongsTo(Booth::class);
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by_user_id');
    }

    public function threads(): HasMany
    {
        return $this->hasMany(BoothThread::class);
    }

    public function promptThread(): HasOne
    {
        return $this->hasOne(BoothThread::class)->where('kind', 'demo_prompt');
    }
}
