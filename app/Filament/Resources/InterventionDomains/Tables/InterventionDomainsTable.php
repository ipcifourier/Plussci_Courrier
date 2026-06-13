<?php

namespace App\Filament\Resources\InterventionDomains\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InterventionDomainsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Domaine')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subdomains_count')
                    ->label('Sous-domaines')
                    ->counts('subdomains')
                    ->badge(),

                TextColumn::make('documents_count')
                    ->label('Documents lies')
                    ->counts('documents')
                    ->badge(),
            ])
            ->actions([
                EditAction::make(),

                DeleteAction::make()
                    ->before(function ($record, DeleteAction $action): void {
                        if ($record->documents()->exists() || $record->subdomains()->exists()) {
                            Notification::make()
                                ->title('Suppression impossible')
                                ->body('Ce domaine est lie a des documents ou contient des sous-domaines.')
                                ->danger()
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->defaultSort('name');
    }
}
