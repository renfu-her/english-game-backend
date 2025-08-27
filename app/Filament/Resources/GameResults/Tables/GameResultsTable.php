<?php

namespace App\Filament\Resources\GameResults\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class GameResultsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('member.name')
                    ->label('Member')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('question.question_text')
                    ->label('Question')
                    ->limit(30)
                    ->searchable(),
                TextColumn::make('user_answer')
                    ->label('User Answer')
                    ->searchable(),
                ToggleColumn::make('is_correct')
                    ->label('Correct'),
                TextColumn::make('score_earned')
                    ->label('Score Earned')
                    ->sortable()
                    ->badge()
                    ->color('success'),
                TextColumn::make('time_taken')
                    ->label('Time (seconds)')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
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
