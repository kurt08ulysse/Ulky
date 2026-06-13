<?php

namespace App\Filament\Resources\StallRentResource\Pages;

use App\Filament\Resources\StallRentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStallRent extends EditRecord
{
    protected static string $resource = StallRentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
