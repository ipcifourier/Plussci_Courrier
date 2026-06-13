<?php

namespace App\Filament\Resources\Tasks\Schemas;

use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('titre')
                ->label('Titre')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Forms\Components\Textarea::make('description')
                ->label('Description')
                ->rows(3)
                ->maxLength(5000)
                ->columnSpanFull(),

            Forms\Components\Select::make('assignee_id')
                ->label('Assigné à')
                ->relationship('assignee', 'name')
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\Select::make('priority')
                ->label('Priorité')
                ->options([
                    'Basse'    => 'Basse',
                    'Normale'  => 'Normale',
                    'Haute'    => 'Haute',
                    'Urgente'  => 'Urgente',
                ])
                ->default('Normale')
                ->required(),

            Forms\Components\Select::make('status')
                ->label('Statut')
                ->options([
                    'todo'      => 'À faire',
                    'doing'     => 'En cours',
                    'done'      => 'Terminé',
                    'cancelled' => 'Annulé',
                ])
                ->default('todo')
                ->required(),

            Forms\Components\DatePicker::make('due_date')
                ->label('Échéance')
                ->displayFormat('d/m/Y')
                ->nullable(),

            Forms\Components\Hidden::make('assigner_id')
                ->default(fn (): int => Auth::id()),
        ]);
    }
}
