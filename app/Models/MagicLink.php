<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MagicLink extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public static function generate(User $user, Event $event, string $purpose = 'login'): array
    {
        $rawToken = Str::random(64);

        static::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        $link = static::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'token_hash' => hash('sha256', $rawToken),
            'purpose' => $purpose,
            'expires_at' => $event->ends_at->addDay(),
        ]);

        return ['link' => $link, 'token' => $rawToken];
    }

    public static function findByToken(string $rawToken): ?static
    {
        return static::where('token_hash', hash('sha256', $rawToken))->first();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->isUsed() && ! $this->isRevoked();
    }

    public function consume(): void
    {
        $this->update(['used_at' => now()]);
    }
}
