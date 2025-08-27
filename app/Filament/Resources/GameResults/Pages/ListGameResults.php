<?php

namespace App\Filament\Resources\GameResults\Pages;

use App\Filament\Resources\GameResults\GameResultResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGameResults extends ListRecords
{
    protected static string $resource = GameResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
