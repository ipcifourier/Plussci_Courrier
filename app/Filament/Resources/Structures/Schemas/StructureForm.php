<?php

namespace App\Filament\Resources\Structures\Schemas;

use App\Models\Structure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StructureForm
{
    public static function configure(Schema $schema): Schema
    {
        $typeOptions = [
            'Université' => 'Université',
             'Centre de Recherche' => 'Centre de Recherche',
            'Entreprise' => 'Entreprise',
            'Administration' => 'Administration',
            'ONG' => 'ONG',
            'Association' => 'Association',
            'Collectivité' => 'Collectivité',
            'Partenaire' => 'Partenaire',
            'Autre' => 'Autre',
        ];

        return $schema
            ->components([
                TextInput::make('nom')
                    ->label('Nom de la structure')
                    ->required(),
                Select::make('type')
                    ->label('Type')
                    ->options(fn (): array => array_reduce(
                        Structure::query()
                            ->whereNotNull('type')
                            ->where('type', '!=', '')
                            ->orderBy('type')
                            ->pluck('type')
                            ->all(),
                        static function (array $carry, string $type) use ($typeOptions): array {
                            $carry[$type] = $typeOptions[$type] ?? $type;

                            return $carry;
                        },
                        $typeOptions,
                    ))
                    ->searchable()
                    ->native(false)
                    ->nullable(),
                TextInput::make('adresse')
                    ->label('Adresse')
                    ->nullable(),
                TextInput::make('email')
                    ->label('Email')
                    ->nullable(),
                TextInput::make('telephone')
                    ->label('Téléphone')
                    ->nullable(),
            ]);
    }
}
