<?php

namespace App\Filament\Resources\Meetings\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ParticipantsRelationManager extends RelationManager
{
    protected static string $relationship = 'participantLinks';

    protected static ?string $title = 'Participants';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('user_id')
                ->label('Utilisateur')
                ->relationship('user', 'name')
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\TextInput::make('role')
                ->label('Role')
                ->maxLength(255)
                ->placeholder('Participant, Rapporteur, Observateur...')
                ->nullable(),

            Forms\Components\Select::make('attendance_status')
                ->label('Statut de participation')
                ->options([
                    'invited' => 'Invite',
                    'accepted' => 'Accepte',
                    'declined' => 'Decline',
                    'present' => 'Present',
                    'absent' => 'Absent',
                ])
                ->default('invited')
                ->required()
                ->native(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('role')
                    ->label('Role')
                    ->placeholder('-')
                    ->searchable(),

                BadgeColumn::make('attendance_status')
                    ->label('Participation')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'invited' => 'Invite',
                        'accepted' => 'Accepte',
                        'declined' => 'Decline',
                        'present' => 'Present',
                        'absent' => 'Absent',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'invited',
                        'info' => 'accepted',
                        'danger' => 'declined',
                        'success' => 'present',
                        'warning' => 'absent',
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Ajouter un participant'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('id');
    }
}
