<?php

namespace App\Filament\Resources\AdministrativeRequestResource\Pages;

use App\Filament\Resources\AdministrativeRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdministrativeRequests extends ListRecords
{
    protected static string $resource = AdministrativeRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
