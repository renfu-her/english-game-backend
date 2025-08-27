<?php

namespace App\Filament\Resources\Questions\Schemas;

use App\Models\Category;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Select::make('category_id')
                            ->label('Category')
                            ->options(Category::pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        Select::make('question_type')
                            ->label('Question Type')
                            ->options([
                                'multiple_choice' => 'Multiple Choice',
                                'fill_blank' => 'Fill in the Blank',
                            ])
                            ->required()
                            ->reactive(),
                        TextInput::make('difficulty_level')
                            ->label('Difficulty Level')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5)
                            ->default(1)
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
                Textarea::make('question_text')
                    ->label('Question Text')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('correct_answer')
                    ->label('Correct Answer')
                    ->required()
                    ->columnSpanFull(),
                KeyValue::make('options')
                    ->label('Multiple Choice Options')
                    ->keyLabel('Option')
                    ->valueLabel('Text')
                    ->addActionLabel('Add Option')
                    ->visible(fn ($get) => $get('question_type') === 'multiple_choice')
                    ->columnSpanFull(),
                Textarea::make('explanation')
                    ->label('Explanation')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
