<?php

namespace App\Filament\Resources\DocumentTypes\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class DocumentTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->label('Nom du type')
                ->required()
                ->maxLength(255)
                ->unique(table: 'document_types', column: 'name', ignoreRecord: true),
        ]);
    }
}
