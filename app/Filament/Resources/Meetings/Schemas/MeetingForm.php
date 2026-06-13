<?php

namespace App\Filament\Resources\Meetings\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class MeetingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('title')
                    ->label('Intitule de la reunion')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('description')
                    ->label('Ordre du jour / description')
                    ->rows(3)
                    ->nullable()
                    ->columnSpanFull(),

                Forms\Components\DateTimePicker::make('starts_at')
                    ->label('Debut')
                    ->required()
                    ->seconds(false)
                    ->native(false),

                Forms\Components\DateTimePicker::make('ends_at')
                    ->label('Fin')
                    ->seconds(false)
                    ->nullable()
                    ->native(false)
                    ->after('starts_at'),

                Forms\Components\TextInput::make('location')
                    ->label('Lieu')
                    ->maxLength(255)
                    ->nullable(),

                Forms\Components\Select::make('facilitator_id')
                    ->label('Animateur')
                    ->relationship('facilitator', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                Forms\Components\Select::make('participants')
                    ->label('Participants')
                    ->multiple()
                    ->relationship('participants', 'name')
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),

                Forms\Components\Select::make('status')
                    ->label('Statut')
                    ->options([
                        'planned' => 'Planifiee',
                        'held' => 'Tenue',
                        'cancelled' => 'Annulee',
                        'postponed' => 'Reportee',
                    ])
                    ->default('planned')
                    ->required()
                    ->native(false),
            ]);
    }
}
