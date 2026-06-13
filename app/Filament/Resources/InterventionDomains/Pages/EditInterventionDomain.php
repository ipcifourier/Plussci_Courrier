<?php

namespace App\Filament\Resources\InterventionDomains\Pages;

use App\Filament\Resources\InterventionDomains\InterventionDomainResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInterventionDomain extends EditRecord
{
    protected static string $resource = InterventionDomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
