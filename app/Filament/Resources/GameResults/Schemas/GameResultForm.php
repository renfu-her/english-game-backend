<?php

namespace App\Filament\Resources\GameResults\Schemas;

use App\Models\Category;
use App\Models\Member;
use App\Models\Question;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class GameResultForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('member_id')
                    ->label('Member')
                    ->options(Member::pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                Select::make('question_id')
                    ->label('Question')
                    ->options(Question::pluck('question_text', 'id'))
                    ->required()
                    ->searchable(),
                Select::make('category_id')
                    ->label('Category')
                    ->options(Category::pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                TextInput::make('user_answer')
                    ->label('User Answer')
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_correct')
                    ->label('Correct Answer'),
                TextInput::make('time_taken')
                    ->label('Time Taken (seconds)')
                    ->numeric()
                    ->minValue(0),
                TextInput::make('score_earned')
                    ->label('Score Earned')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
            ]);
    }
}
