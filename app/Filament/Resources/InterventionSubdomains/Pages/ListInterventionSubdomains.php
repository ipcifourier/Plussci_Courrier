<?php

namespace App\Filament\Resources\InterventionSubdomains\Pages;

use App\Filament\Resources\InterventionSubdomains\InterventionSubdomainResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInterventionSubdomains extends ListRecords
{
    protected static string $resource = InterventionSubdomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
