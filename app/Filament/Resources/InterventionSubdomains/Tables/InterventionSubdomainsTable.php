<?php

namespace App\Filament\Resources\InterventionSubdomains\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InterventionSubdomainsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Sous-domaine')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('domain.name')
                    ->label('Domaine')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('documents_count')
                    ->label('Documents lies')
                    ->counts('documents')
                    ->badge(),
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
            ])
            ->defaultSort('name');
    }
}
