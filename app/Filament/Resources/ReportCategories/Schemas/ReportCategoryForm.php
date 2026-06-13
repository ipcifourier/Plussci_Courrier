<?php

namespace App\Filament\Resources\ReportCategories\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class ReportCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->label('Categorie')
                ->required()
                ->maxLength(255)
                ->unique(table: 'report_categories', column: 'name', ignoreRecord: true),

            Forms\Components\Toggle::make('is_active')
                ->label('Actif')
                ->default(true),
        ]);
    }
}
