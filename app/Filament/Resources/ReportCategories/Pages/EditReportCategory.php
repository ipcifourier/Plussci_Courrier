<?php

namespace App\Filament\Resources\ReportCategories\Pages;

use App\Filament\Resources\ReportCategories\ReportCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReportCategory extends EditRecord
{
    protected static string $resource = ReportCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
