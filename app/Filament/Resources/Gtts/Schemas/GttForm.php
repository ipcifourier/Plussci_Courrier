<?php

namespace App\Filament\Resources\Gtts\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class GttForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->label('Nom du GTT')
                ->required()
                ->maxLength(255)
                ->unique(table: 'gtts', column: 'name', ignoreRecord: true),
            Forms\Components\Select::make('responsable')
                ->label('Responsable du GTT')
                ->options(fn () => \App\Models\User::query()
                    ->orderBy('name')
                    ->pluck('name', 'id'))
                ->searchable()
                ->nullable()
                ->helperText('Le responsable sélectionné sera rattaché à ce GTT et synchronisé avec le rôle GTT Responsable.'),
            Forms\Components\Textarea::make('description')
                ->label('Description')
                ->nullable(),
        ]);
    }
}
