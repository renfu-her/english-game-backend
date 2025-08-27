<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'category_id',
        'questions_attempted',
        'questions_correct',
        'total_score',
        'completion_percentage',
        'last_played_at',
    ];

    protected $casts = [
        'questions_attempted' => 'integer',
        'questions_correct' => 'integer',
        'total_score' => 'integer',
        'completion_percentage' => 'decimal:2',
        'last_played_at' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
