<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'question_text',
        'question_type', // 'multiple_choice', 'fill_blank'
        'correct_answer',
        'options', // JSON for multiple choice options
        'explanation',
        'difficulty_level',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'is_active' => 'boolean',
        'difficulty_level' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function gameResults()
    {
        return $this->hasMany(GameResult::class);
    }
}
