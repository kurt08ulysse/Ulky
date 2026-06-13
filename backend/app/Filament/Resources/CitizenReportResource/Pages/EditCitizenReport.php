<?php

namespace App\Filament\Resources\CitizenReportResource\Pages;

use App\Filament\Resources\CitizenReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCitizenReport extends EditRecord
{
    protected static string $resource = CitizenReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
