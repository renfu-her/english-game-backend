<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameRoomPlayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_room_id',
        'member_id',
        'is_ready',
        'current_score',
        'answers_correct',
        'answers_incorrect',
        'joined_at',
        'left_at',
    ];

    protected $casts = [
        'is_ready' => 'boolean',
        'current_score' => 'integer',
        'answers_correct' => 'integer',
        'answers_incorrect' => 'integer',
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    public function gameRoom(): BelongsTo
    {
        return $this->belongsTo(GameRoom::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function isActive(): bool
    {
        return is_null($this->left_at);
    }

    public function getAccuracyRate(): float
    {
        $total = $this->answers_correct + $this->answers_incorrect;
        return $total > 0 ? round(($this->answers_correct / $total) * 100, 2) : 0;
    }
}
