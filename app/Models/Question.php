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

    /**
     * Get the option texts as an array
     */
    public function getOptionTexts(): array
    {
        if (!is_array($this->options)) {
            return [];
        }

        return array_map(function ($option) {
            return $option['text'] ?? '';
        }, $this->options);
    }

    /**
     * Check if the correct answer is valid for multiple choice questions
     */
    public function isCorrectAnswerValid(): bool
    {
        if ($this->question_type !== 'multiple_choice') {
            return true;
        }

        $optionTexts = $this->getOptionTexts();
        return in_array($this->correct_answer, $optionTexts);
    }

    /**
     * Get the number of options for multiple choice questions
     */
    public function getOptionsCount(): int
    {
        if (!is_array($this->options)) {
            return 0;
        }

        return count($this->options);
    }

    /**
     * Check if this is a multiple choice question
     */
    public function isMultipleChoice(): bool
    {
        return $this->question_type === 'multiple_choice';
    }

    /**
     * Check if this is a fill in the blank question
     */
    public function isFillInTheBlank(): bool
    {
        return $this->question_type === 'fill_blank';
    }
}
