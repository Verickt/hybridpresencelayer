<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password', 'company', 'role_title', 'intent', 'linkedin_url', 'phone', 'is_organizer', 'is_invisible'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_organizer' => 'boolean',
            'is_invisible' => 'boolean',
        ];
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)
            ->withPivot([
                'participant_type', 'status', 'context_badge',
                'icebreaker_answer', 'open_to_call',
                'available_after_session', 'last_active_at',
            ])
            ->withTimestamps();
    }

    public function interestTags(): BelongsToMany
    {
        return $this->belongsToMany(InterestTag::class, 'user_interest_tag')
            ->withPivot('event_id');
    }

    public function organizedEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'organizer_id');
    }
}
