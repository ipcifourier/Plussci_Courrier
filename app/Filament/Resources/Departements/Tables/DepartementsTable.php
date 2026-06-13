<?php

namespace App\Filament\Resources\Departements\Tables;

use App\Models\Departement;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DepartementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('nom')
            ->columns([
                TextColumn::make('nom')
                    ->label('Département')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('responsable')
                    ->label('Responsable')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Non renseigné')
                    ->badge()
                    ->color('info'),
                TextColumn::make('users_count')
                    ->label('Utilisateurs')
                    ->counts('users')
                    ->badge()
                    ->sortable()
                    ->color('primary'),
                TextColumn::make('description')
                    ->label('Description')
                    ->placeholder('Aucune description')
                    ->limit(70)
                    ->tooltip(fn (Departement $record): ?string => filled($record->description) ? $record->description : null)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Mis à jour le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('responsable')
                    ->label('Responsable renseigné')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('responsable')->where('responsable', '!=', '')),
                TernaryFilter::make('avec_description')
                    ->label('Description')
                    ->placeholder('Toutes')
                    ->trueLabel('Avec description')
                    ->falseLabel('Sans description')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('description')->where('description', '!=', ''),
                        false: fn (Builder $query): Builder => $query->where(function (Builder $subQuery): void {
                            $subQuery->whereNull('description')->orWhere('description', '');
                        }),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Modifier')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning'),
                DeleteAction::make()
                    ->label('Supprimer')
                    ->icon('heroicon-o-trash')
                    ->visible(fn (Departement $record): bool => $record->users()->count() === 0),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Supprimer la sélection'),
                ]),
            ]);
    }
}
