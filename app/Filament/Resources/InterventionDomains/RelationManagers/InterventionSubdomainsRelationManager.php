<?php

namespace App\Filament\Resources\InterventionDomains\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InterventionSubdomainsRelationManager extends RelationManager
{
    protected static string $relationship = 'subdomains';

    protected static ?string $title = 'Sous-domaines';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->label('Sous-domaine')
                ->required()
                ->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Sous-domaine')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('documents_count')
                    ->label('Documents lies')
                    ->counts('documents')
                    ->badge(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),

                DeleteAction::make()
                    ->before(function ($record, DeleteAction $action): void {
                        if ($record->documents()->exists()) {
                            Notification::make()
                                ->title('Suppression impossible')
                                ->body('Ce sous-domaine est lie a des documents.')
                                ->danger()
                                ->send();

                            $action->cancel();
                        }
                    }),
            ]);
    }
}
