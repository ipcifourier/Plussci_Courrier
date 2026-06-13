<?php

namespace App\Filament\Resources\Appointments\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('title')
                    ->label('Objet du rendez-vous')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('description')
                    ->label('Description')
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

                Forms\Components\Select::make('assigned_to')
                    ->label('Assigne a')
                    ->relationship('assignee', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                Forms\Components\TextInput::make('contact_name')
                    ->label('Contact')
                    ->maxLength(255)
                    ->nullable(),

                Forms\Components\TextInput::make('contact_phone')
                    ->label('Telephone contact')
                    ->tel()
                    ->maxLength(50)
                    ->nullable(),

                Forms\Components\Select::make('status')
                    ->label('Statut')
                    ->options([
                        'planned' => 'Planifie',
                        'confirmed' => 'Confirme',
                        'effective' => 'Effectue',
                        'cancelled' => 'Annule',
                        'rescheduled' => 'Reprogramme',
                    ])
                    ->default('planned')
                    ->required()
                    ->native(false),

                Forms\Components\Select::make('type')
                    ->label('Type')
                    ->options([
                        'rendez_vous' => 'Rendez-vous',
                        'diligence'   => 'Diligence',
                    ])
                    ->default('rendez_vous')
                    ->required()
                    ->native(false),

                // A4 — Récurrence
                Forms\Components\Select::make('recurrence_rule')
                    ->label('Récurrence')
                    ->options([
                        'none'    => 'Aucune',
                        'daily'   => 'Quotidienne',
                        'weekly'  => 'Hebdomadaire',
                        'monthly' => 'Mensuelle',
                    ])
                    ->default('none')
                    ->native(false)
                    ->live(),

                Forms\Components\DatePicker::make('recurrence_ends_at')
                    ->label('Fin de récurrence')
                    ->native(false)
                    ->nullable()
                    ->visible(fn ($get): bool => $get('recurrence_rule') !== 'none')
                    ->helperText('Laisser vide pour une récurrence indéfinie.'),

                Forms\Components\Hidden::make('created_by')
                    ->default(fn (): ?int => Auth::id()),
            ]);
    }
}
