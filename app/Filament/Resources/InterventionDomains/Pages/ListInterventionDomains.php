<?php

namespace App\Filament\Resources\InterventionDomains\Pages;

use App\Filament\Resources\InterventionDomains\InterventionDomainResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInterventionDomains extends ListRecords
{
    protected static string $resource = InterventionDomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
