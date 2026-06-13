<?php

namespace App\Filament\Resources\InterventionSubdomains\Schemas;

use App\Models\InterventionDomain;
use Filament\Forms;
use Filament\Schemas\Schema;

class InterventionSubdomainForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('intervention_domain_id')
                ->label('Domaine')
                ->options(fn () => InterventionDomain::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->required()
                ->preload()
                ->native(false),

            Forms\Components\TextInput::make('name')
                ->label('Sous-domaine')
                ->required()
                ->maxLength(255),
        ]);
    }
}
