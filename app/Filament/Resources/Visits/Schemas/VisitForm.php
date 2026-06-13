<?php

namespace App\Filament\Resources\Visits\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class VisitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('appointment_id')
                    ->label('Rendez-vous associe')
                    ->relationship('appointment', 'title')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                Forms\Components\TextInput::make('visitor_last_name')
                    ->label('Nom du visiteur')
                    ->maxLength(255)
                    ->nullable(),

                Forms\Components\TextInput::make('visitor_first_name')
                    ->label('Prenoms du visiteur')
                    ->maxLength(255)
                    ->nullable(),

                Forms\Components\TextInput::make('visitor_structure')
                    ->label('Structure du visiteur')
                    ->maxLength(255)
                    ->nullable(),

                Forms\Components\DateTimePicker::make('happened_at')
                    ->label('Date et heure de debut')
                    ->required()
                    ->seconds(false)
                    ->native(false),

                Forms\Components\DateTimePicker::make('ended_at')
                    ->label('Heure de fin de la visite')
                    ->seconds(false)
                    ->nullable()
                    ->native(false)
                    ->after('happened_at')
                    ->rule('after_or_equal:happened_at')
                    ->validationMessages([
                        'after_or_equal' => 'L\'heure de fin doit etre superieure ou egale a la date et heure de debut.',
                    ]),

                Forms\Components\TextInput::make('location')
                    ->label('Lieu')
                    ->maxLength(255)
                    ->nullable(),

                Forms\Components\Textarea::make('summary')
                    ->label('Compte rendu')
                    ->rows(4)
                    ->nullable()
                    ->columnSpanFull(),

                Forms\Components\Hidden::make('created_by')
                    ->default(fn (): ?int => Auth::id()),
            ]);
    }
}
