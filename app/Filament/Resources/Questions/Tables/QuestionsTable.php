<?php

namespace App\Filament\Resources\Questions\Tables;

use App\Models\Question;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('question_text')
                    ->label('Question')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('question_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'multiple_choice' => 'Multiple Choice',
                        'fill_blank' => 'Fill Blank',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'multiple_choice' => 'success',
                        'fill_blank' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('options_count')
                    ->label('Options')
                    ->state(function (Question $record): string {
                        if ($record->isMultipleChoice()) {
                            $count = $record->getOptionsCount();
                            return "{$count} options";
                        }
                        return '-';
                    })
                    ->badge()
                    ->color(function (Question $record): string {
                        if (!$record->isMultipleChoice()) {
                            return 'gray';
                        }
                        
                        $count = $record->getOptionsCount();
                        return match (true) {
                            $count >= 4 => 'success',
                            $count >= 2 => 'warning',
                            default => 'danger',
                        };
                    }),
                TextColumn::make('correct_answer')
                    ->label('Correct Answer')
                    ->searchable()
                    ->limit(30),
                IconColumn::make('validation_status')
                    ->label('Valid')
                    ->boolean()
                    ->getStateUsing(function (Question $record): bool {
                        if ($record->isMultipleChoice()) {
                            return $record->isCorrectAnswerValid();
                        }
                        return true;
                    })
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(function (Question $record): string {
                        if ($record->isMultipleChoice()) {
                            return $record->isCorrectAnswerValid() 
                                ? 'Correct answer is valid' 
                                : 'Correct answer is not in options';
                        }
                        return 'Not applicable';
                    }),
                TextColumn::make('difficulty_level')
                    ->label('Difficulty')
                    ->badge()
                    ->color(fn (int $state): string => match ($state) {
                        1 => 'success',
                        2 => 'info',
                        3 => 'warning',
                        4, 5 => 'danger',
                        default => 'gray',
                    }),
                ToggleColumn::make('is_active')
                    ->label('Active'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->label('Filter by Category'),
                SelectFilter::make('question_type')
                    ->options([
                        'multiple_choice' => 'Multiple Choice',
                        'fill_blank' => 'Fill in the Blank',
                    ])
                    ->label('Filter by Type'),
                SelectFilter::make('difficulty_level')
                    ->options([
                        1 => 'Level 1 - Beginner',
                        2 => 'Level 2 - Elementary',
                        3 => 'Level 3 - Intermediate',
                        4 => 'Level 4 - Advanced',
                        5 => 'Level 5 - Expert',
                    ])
                    ->label('Filter by Difficulty'),
                // Note: Validation filter removed due to SQLite compatibility
                // For MySQL/PostgreSQL, you can use JSON_CONTAINS or similar functions
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
