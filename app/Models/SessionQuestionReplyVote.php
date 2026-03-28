<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionQuestionReplyVote extends Model
{
    protected $fillable = [
        'session_question_reply_id',
        'user_id',
    ];

    public function reply(): BelongsTo
    {
        return $this->belongsTo(SessionQuestionReply::class, 'session_question_reply_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
