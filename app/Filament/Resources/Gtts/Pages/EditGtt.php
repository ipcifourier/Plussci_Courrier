<?php

namespace App\Filament\Resources\Gtts\Pages;

use App\Filament\Resources\Gtts\GttResource;
use App\Services\GttRoleSyncService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGtt extends EditRecord
{
    protected static string $resource = GttResource::class;

    protected mixed $previousResponsable = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->previousResponsable = $this->record->getOriginal('responsable');

        return $data;
    }

    protected function afterSave(): void
    {
        app(GttRoleSyncService::class)->syncGttResponsable($this->record, $this->previousResponsable);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
