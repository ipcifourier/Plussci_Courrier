<?php

namespace App\Filament\Resources\ReportTemplates\Pages;

use App\Filament\Resources\ReportTemplates\ReportTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditReportTemplate extends EditRecord
{
    protected static string $resource = ReportTemplateResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['is_validated'] ?? false) === true && ! $this->record->validated_at) {
            $data['validated_by'] = Auth::id();
            $data['validated_at'] = now();
        }

        if (($data['is_validated'] ?? false) === false) {
            $data['validated_by'] = null;
            $data['validated_at'] = null;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
