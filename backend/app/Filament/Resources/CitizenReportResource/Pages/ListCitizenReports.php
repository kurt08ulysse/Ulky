<?php

namespace App\Filament\Resources\CitizenReportResource\Pages;

use App\Filament\Resources\CitizenReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCitizenReports extends ListRecords
{
    protected static string $resource = CitizenReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
