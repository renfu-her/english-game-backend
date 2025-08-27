<?php

namespace App\Filament\Resources\GameResults;

use App\Filament\Resources\GameResults\Pages\CreateGameResult;
use App\Filament\Resources\GameResults\Pages\EditGameResult;
use App\Filament\Resources\GameResults\Pages\ListGameResults;
use App\Filament\Resources\GameResults\Schemas\GameResultForm;
use App\Filament\Resources\GameResults\Tables\GameResultsTable;
use App\Models\GameResult;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GameResultResource extends Resource
{
    protected static ?string $model = GameResult::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?string $recordTitleAttribute = 'id';



    public static function form(Schema $schema): Schema
    {
        return GameResultForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GameResultsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGameResults::route('/'),
            'create' => CreateGameResult::route('/create'),
            'edit' => EditGameResult::route('/{record}/edit'),
        ];
    }
}
