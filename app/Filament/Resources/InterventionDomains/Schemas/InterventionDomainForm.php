<?php

namespace App\Filament\Resources\InterventionDomains\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class InterventionDomainForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->label('Domaine')
                ->required()
                ->maxLength(255)
                ->unique(table: 'intervention_domains', column: 'name', ignoreRecord: true),
        ]);
    }
}
