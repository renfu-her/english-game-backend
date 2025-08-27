<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'difficulty_level',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'difficulty_level' => 'integer',
    ];

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function categoryProgress()
    {
        return $this->hasMany(CategoryProgress::class);
    }
}
