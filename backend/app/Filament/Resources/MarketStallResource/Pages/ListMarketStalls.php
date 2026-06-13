<?php

namespace App\Filament\Resources\MarketStallResource\Pages;

use App\Filament\Resources\MarketStallResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMarketStalls extends ListRecords
{
    protected static string $resource = MarketStallResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
