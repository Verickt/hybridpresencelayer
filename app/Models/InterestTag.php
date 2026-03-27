<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class InterestTag extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (InterestTag $tag) {
            $tag->slug ??= Str::slug($tag->name);
        });
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class);
    }
}
