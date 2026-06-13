<?php

namespace App\Filament\Resources\Gtts\Pages;

use App\Filament\Resources\Gtts\GttResource;
use App\Services\GttRoleSyncService;
use Filament\Resources\Pages\CreateRecord;

class CreateGtt extends CreateRecord
{
    protected static string $resource = GttResource::class;

    protected function afterCreate(): void
    {
        app(GttRoleSyncService::class)->syncGttResponsable($this->record);
    }
}
