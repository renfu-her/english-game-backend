<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'status',
        'max_players',
        'current_players',
        'owner_id',
        'category_id',
        'current_question_id',
        'current_round',
        'total_rounds',
        'time_per_question',
        'started_at',
        'ended_at',
        'settings',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'settings' => 'array',
        'max_players' => 'integer',
        'current_players' => 'integer',
        'current_round' => 'integer',
        'total_rounds' => 'integer',
        'time_per_question' => 'integer',
    ];

    // Room statuses
    const STATUS_WAITING = 'waiting';
    const STATUS_PLAYING = 'playing';
    const STATUS_FINISHED = 'finished';
    const STATUS_PAUSED = 'paused';

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'owner_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function currentQuestion(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'current_question_id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(GameRoomPlayer::class);
    }

    public function gameResults(): HasMany
    {
        return $this->hasMany(GameRoomResult::class);
    }

    public function isFull(): bool
    {
        return $this->current_players >= $this->max_players;
    }

    public function canStart(): bool
    {
        return $this->status === self::STATUS_WAITING && $this->current_players >= 2;
    }

    public function isOwner(Member $member): bool
    {
        return $this->owner_id === $member->id;
    }

    public function hasPlayer(Member $member): bool
    {
        return $this->players()->where('member_id', $member->id)->exists();
    }

    public function getPlayer(Member $member): ?GameRoomPlayer
    {
        return $this->players()->where('member_id', $member->id)->first();
    }

    public function generateCode(): string
    {
        return strtoupper(substr(md5(uniqid()), 0, 6));
    }
}
