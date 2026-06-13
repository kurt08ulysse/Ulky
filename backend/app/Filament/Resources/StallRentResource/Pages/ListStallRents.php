<?php

namespace App\Filament\Resources\StallRentResource\Pages;

use App\Filament\Resources\StallRentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStallRents extends ListRecords
{
    protected static string $resource = StallRentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
