<?php

namespace App\Filament\Resources\Structures\Tables;

use App\Models\Structure;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StructuresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('nom')
            ->columns([
                TextColumn::make('nom')
                    ->label('Structure')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('type')
                    ->label('Type')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (?string $state): string => filled($state) ? 'primary' : 'gray')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? $state : 'Non renseigné'),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->placeholder('Non renseigné')
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('telephone')
                    ->label('Téléphone')
                    ->searchable()
                    ->placeholder('Non renseigné')
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('adresse')
                    ->label('Adresse')
                    ->placeholder('Non renseignée')
                    ->limit(45)
                    ->tooltip(fn (Structure $record): ?string => filled($record->adresse) ? $record->adresse : null)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(fn (): array => Structure::query()
                        ->whereNotNull('type')
                        ->where('type', '!=', '')
                        ->orderBy('type')
                        ->pluck('type', 'type')
                        ->all()),
                TernaryFilter::make('avec_email')
                    ->label('Email')
                    ->placeholder('Tous')
                    ->trueLabel('Avec email')
                    ->falseLabel('Sans email')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('email')->where('email', '!=', ''),
                        false: fn (Builder $query): Builder => $query->where(function (Builder $subQuery): void {
                            $subQuery->whereNull('email')->orWhere('email', '');
                        }),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                TernaryFilter::make('avec_telephone')
                    ->label('Téléphone')
                    ->placeholder('Tous')
                    ->trueLabel('Avec téléphone')
                    ->falseLabel('Sans téléphone')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('telephone')->where('telephone', '!=', ''),
                        false: fn (Builder $query): Builder => $query->where(function (Builder $subQuery): void {
                            $subQuery->whereNull('telephone')->orWhere('telephone', '');
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
                    ->visible(fn (Structure $record): bool => $record->bureauMembers()->count() === 0),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Supprimer la sélection'),
                ]),
            ]);
    }
}
