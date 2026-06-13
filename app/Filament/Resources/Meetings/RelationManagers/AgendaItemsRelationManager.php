<?php

namespace App\Filament\Resources\Meetings\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AgendaItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'agendaItems';

    protected static ?string $title = 'Ordre du jour';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('title')
                ->label('Point')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Forms\Components\Textarea::make('description')
                ->label('Description')
                ->rows(3)
                ->nullable()
                ->columnSpanFull(),

            Forms\Components\TextInput::make('position')
                ->label('Ordre')
                ->numeric()
                ->default(1)
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('position')
                    ->label('Ordre')
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Point')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(80)
                    ->placeholder('-'),

                TextColumn::make('tasks_count')
                    ->label('Diligences')
                    ->counts('tasks')
                    ->badge(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Ajouter un point'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('position');
    }
}
