<?php

namespace App\Filament\Resources\DocumentTypes\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Type')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('documents_count')
                    ->label('Documents lies')
                    ->counts('documents')
                    ->badge()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Cree le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function ($record, DeleteAction $action): void {
                        if ($record->documents()->exists()) {
                            Notification::make()
                                ->title('Suppression impossible')
                                ->body('Ce type est lie a des documents.')
                                ->danger()
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->defaultSort('name');
    }
}
