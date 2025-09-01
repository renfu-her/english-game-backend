<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameRoomResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_room_id',
        'member_id',
        'question_id',
        'user_answer',
        'is_correct',
        'time_taken',
        'score_earned',
        'round_number',
        'answered_at',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'time_taken' => 'integer',
        'score_earned' => 'integer',
        'round_number' => 'integer',
        'answered_at' => 'datetime',
    ];

    public function gameRoom(): BelongsTo
    {
        return $this->belongsTo(GameRoom::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
