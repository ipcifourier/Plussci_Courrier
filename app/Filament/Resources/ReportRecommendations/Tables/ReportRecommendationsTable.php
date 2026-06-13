<?php

namespace App\Filament\Resources\ReportRecommendations\Tables;

use App\Models\ReportCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReportRecommendationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('report.reference')
                    ->label('Rapport')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('report.category.name')
                    ->label('Type rapport')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('recommendation')
                    ->label('Recommandation')
                    ->limit(70)
                    ->searchable(),

                TextColumn::make('responsible.name')
                    ->label('Responsable')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Delai')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record): ?string => $record->isOverdue() ? 'danger' : null),

                BadgeColumn::make('implementation_status')
                    ->label('Statut')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'not_started' => 'Non demarree',
                        'in_progress' => 'En cours',
                        'implemented' => 'Mise en oeuvre',
                        'partially_implemented' => 'Partielle',
                        'blocked' => 'Bloquee',
                        'cancelled' => 'Annulee',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'not_started',
                        'info' => 'in_progress',
                        'success' => 'implemented',
                        'warning' => 'partially_implemented',
                        'danger' => 'blocked',
                        'secondary' => 'cancelled',
                    ]),

                TextColumn::make('progress_percent')
                    ->label('Avancement')
                    ->suffix('%')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('implementation_status')
                    ->label('Statut')
                    ->options([
                        'not_started' => 'Non demarree',
                        'in_progress' => 'En cours',
                        'implemented' => 'Mise en oeuvre',
                        'partially_implemented' => 'Partielle',
                        'blocked' => 'Bloquee',
                        'cancelled' => 'Annulee',
                    ]),

                SelectFilter::make('report_category_id')
                    ->label('Categorie rapport')
                    ->options(fn () => ReportCategory::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'] ?? null)) {
                            return $query;
                        }

                        return $query->whereHas('report', fn (Builder $q) => $q->where('report_category_id', $data['value']));
                    }),

                SelectFilter::make('responsible_user_id')
                    ->label('Responsable')
                    ->relationship('responsible', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('overdue')
                    ->label('En retard')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereDate('due_date', '<', now())
                        ->whereNotIn('implementation_status', ['implemented', 'cancelled'])),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('due_date', 'asc');
    }
}
