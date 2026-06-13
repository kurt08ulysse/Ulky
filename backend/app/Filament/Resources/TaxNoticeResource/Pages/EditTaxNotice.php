<?php

namespace App\Filament\Resources\TaxNoticeResource\Pages;

use App\Filament\Resources\TaxNoticeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTaxNotice extends EditRecord
{
    protected static string $resource = TaxNoticeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
