<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessionQuestionReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_question_id',
        'user_id',
        'body',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(SessionQuestion::class, 'session_question_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(SessionQuestionReplyVote::class);
    }
}
