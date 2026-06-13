<?php

namespace App\Filament\Resources\ElectedOfficialResource\Pages;

use App\Filament\Resources\ElectedOfficialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditElectedOfficial extends EditRecord
{
    protected static string $resource = ElectedOfficialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
