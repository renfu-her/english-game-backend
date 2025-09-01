<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Member extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $guard = 'member';

    protected $fillable = [
        'name',
        'email',
        'password',
        'score',
        'level',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'score' => 'integer',
            'level' => 'integer',
        ];
    }

    public function gameResults()
    {
        return $this->hasMany(GameResult::class);
    }

    public function categoryProgress()
    {
        return $this->hasMany(CategoryProgress::class);
    }

    // Game Room relationships
    public function ownedGameRooms()
    {
        return $this->hasMany(GameRoom::class, 'owner_id');
    }

    public function gameRoomPlayers()
    {
        return $this->hasMany(GameRoomPlayer::class);
    }

    public function gameRoomResults()
    {
        return $this->hasMany(GameRoomResult::class);
    }

    public function joinedGameRooms()
    {
        return $this->belongsToMany(GameRoom::class, 'game_room_players')
            ->withPivot(['is_ready', 'current_score', 'answers_correct', 'answers_incorrect', 'joined_at', 'left_at'])
            ->withTimestamps();
    }
}
