<?php

namespace App\Filament\Resources\InterventionSubdomains\Pages;

use App\Filament\Resources\InterventionSubdomains\InterventionSubdomainResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInterventionSubdomain extends EditRecord
{
    protected static string $resource = InterventionSubdomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
