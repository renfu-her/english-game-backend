<?php

namespace App\Filament\Resources\Questions\Schemas;

use App\Models\Category;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
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
                            ->live()
                            ->afterStateUpdated(function ($set) {
                                // Clear options when switching to fill_blank
                                $set('options', []);
                                $set('correct_answer', '');
                            }),
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
                
                // Multiple Choice Options Section
                Repeater::make('options')
                    ->label('Multiple Choice Options')
                    ->schema([
                        TextInput::make('text')
                            ->label('Option Text')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->defaultItems(4)
                    ->minItems(2)
                    ->maxItems(6)
                    ->reorderableWithButtons()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['text'] ?? null)
                    ->visible(fn ($get) => $get('question_type') === 'multiple_choice')
                    ->columnSpanFull(),
                
                // Correct Answer Selection
                Select::make('correct_answer')
                    ->label('Correct Answer')
                    ->options(function ($get) {
                        $options = $get('options');
                        if (!is_array($options)) {
                            return [];
                        }
                        
                        $choices = [];
                        foreach ($options as $index => $option) {
                            if (isset($option['text']) && !empty($option['text'])) {
                                $choices[$option['text']] = $option['text'];
                            }
                        }
                        
                        return $choices;
                    })
                    ->required()
                    ->visible(fn ($get) => $get('question_type') === 'multiple_choice')
                    ->searchable()
                    ->placeholder('Select the correct answer from the options above')
                    ->rules([
                        function ($get) {
                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                if ($get('question_type') === 'multiple_choice') {
                                    $options = $get('options');
                                    if (is_array($options)) {
                                        $optionTexts = array_map(function ($option) {
                                            return $option['text'] ?? '';
                                        }, $options);
                                        
                                        if (!in_array($value, $optionTexts)) {
                                            $fail('The correct answer must be one of the multiple choice options.');
                                        }
                                    }
                                }
                            };
                        }
                    ])
                    ->columnSpanFull(),
                
                // For Fill in the Blank questions
                TextInput::make('correct_answer')
                    ->label('Correct Answer')
                    ->required()
                    ->visible(fn ($get) => $get('question_type') === 'fill_blank')
                    ->columnSpanFull(),
                
                Textarea::make('explanation')
                    ->label('Explanation')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
