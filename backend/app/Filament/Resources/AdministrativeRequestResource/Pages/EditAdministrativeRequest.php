<?php

namespace App\Filament\Resources\AdministrativeRequestResource\Pages;

use App\Filament\Resources\AdministrativeRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdministrativeRequest extends EditRecord
{
    protected static string $resource = AdministrativeRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
