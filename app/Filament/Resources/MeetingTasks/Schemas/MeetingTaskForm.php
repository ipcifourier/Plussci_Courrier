<?php

namespace App\Filament\Resources\MeetingTasks\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class MeetingTaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('meeting_id')
                    ->label('Reunion')
                    ->relationship('meeting', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('agenda_item_id')
                    ->label('Point d\'ordre du jour')
                    ->relationship('agendaItem', 'title')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                Forms\Components\TextInput::make('title')
                    ->label('Diligence')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->nullable()
                    ->columnSpanFull(),

                Forms\Components\Select::make('assigned_to')
                    ->label('Assigne a')
                    ->relationship('assignee', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                Forms\Components\DateTimePicker::make('due_at')
                    ->label('Echeance')
                    ->seconds(false)
                    ->nullable()
                    ->native(false),

                Forms\Components\Select::make('status')
                    ->label('Statut')
                    ->options([
                        'todo' => 'A faire',
                        'in_progress' => 'En cours',
                        'done' => 'Terminee',
                    ])
                    ->default('todo')
                    ->required()
                    ->native(false),

                Forms\Components\Select::make('priority')
                    ->label('Priorite')
                    ->options([
                        'low' => 'Basse',
                        'normal' => 'Normale',
                        'high' => 'Haute',
                        'urgent' => 'Urgente',
                    ])
                    ->default('normal')
                    ->required()
                    ->native(false),

                Forms\Components\DateTimePicker::make('completed_at')
                    ->label('Date de cloture')
                    ->seconds(false)
                    ->nullable()
                    ->native(false)
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Renseignee automatiquement quand le statut passe a "Terminee".'),
            ]);
    }
}
