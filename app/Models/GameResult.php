<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'question_id',
        'category_id',
        'user_answer',
        'is_correct',
        'time_taken',
        'score_earned',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'time_taken' => 'integer',
        'score_earned' => 'integer',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
