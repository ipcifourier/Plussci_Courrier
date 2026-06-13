<?php

namespace App\Filament\Resources\Visits\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class VisitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('happened_at')
                    ->label('Debut visite')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('ended_at')
                    ->label('Fin visite')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('visitor_last_name')
                    ->label('Nom')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('visitor_first_name')
                    ->label('Prenoms')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('visitor_structure')
                    ->label('Structure')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('appointment.title')
                    ->label('Rendez-vous')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('location')
                    ->label('Lieu')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('summary')
                    ->label('Compte rendu')
                    ->limit(70)
                    ->placeholder('-'),

                TextColumn::make('creator.name')
                    ->label('Saisi par')
                    ->searchable()
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('appointment_id')
                    ->label('Rendez-vous')
                    ->relationship('appointment', 'title')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('created_by')
                    ->label('Saisi par')
                    ->relationship('creator', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('mes_saisies')
                    ->label('Mes saisies')
                    ->query(fn (Builder $query): Builder => $query->where('created_by', Auth::id()))
                    ->toggle(),

                Filter::make('semaine_en_cours')
                    ->label('Semaine en cours')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereBetween('happened_at', [now()->startOfWeek(), now()->endOfWeek()])
                    )
                    ->toggle(),

                Filter::make('periode')
                    ->label('Periode')
                    ->form([
                        DatePicker::make('from')->label('Du')->native(false),
                        DatePicker::make('until')->label('Au')->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('happened_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('happened_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('happened_at', 'desc');
    }
}
