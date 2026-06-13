<?php

namespace App\Filament\Resources\Courriers\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * C5 — Gestionnaire de la relation documents ↔ courrier.
 */
class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents associés';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('titre')
            ->columns([
                TextColumn::make('reference_doc')
                    ->label('Référence')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('titre')
                    ->label('Titre')
                    ->searchable()
                    ->limit(60),

                TextColumn::make('type_document')
                    ->label('Type')
                    ->sortable(),

                TextColumn::make('etat_cycle_vie')
                    ->label('État')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Brouillon'   => 'gray',
                        'En révision' => 'warning',
                        'Publié'      => 'success',
                        'Archivé'     => 'danger',
                        default       => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([])
            ->headerActions([])
            ->recordActions([
                Action::make('download')
                    ->label('Télécharger')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record): string => $record->file_path ? asset('storage/' . $record->file_path) : '#')
                    ->openUrlInNewTab()
                    ->visible(fn ($record): bool => (bool) $record->file_path),
            ])
            ->toolbarActions([])
            ->defaultSort('created_at', 'desc');
    }
}
