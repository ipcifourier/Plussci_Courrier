<?php

namespace App\Filament\Resources\ReportCategories\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReportCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Categorie')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),

                TextColumn::make('reports_count')
                    ->label('Rapports')
                    ->counts('reports')
                    ->badge(),

                TextColumn::make('templates_count')
                    ->label('Modeles')
                    ->counts('templates')
                    ->badge(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function ($record, DeleteAction $action): void {
                        if ($record->reports()->exists() || $record->templates()->exists()) {
                            Notification::make()
                                ->title('Suppression impossible')
                                ->body('Cette categorie est deja utilisee.')
                                ->danger()
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->defaultSort('name');
    }
}
