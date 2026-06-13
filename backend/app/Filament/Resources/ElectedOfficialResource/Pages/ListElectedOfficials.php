<?php

namespace App\Filament\Resources\ElectedOfficialResource\Pages;

use App\Filament\Resources\ElectedOfficialResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListElectedOfficials extends ListRecords
{
    protected static string $resource = ElectedOfficialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
