<?php

namespace App\Filament\Resources\MarketStallResource\Pages;

use App\Filament\Resources\MarketStallResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMarketStall extends EditRecord
{
    protected static string $resource = MarketStallResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
