<?php

namespace App\Filament\Resources\Appointments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Objet')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('starts_at')
                    ->label('Debut')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('ends_at')
                    ->label('Fin')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('location')
                    ->label('Lieu')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('assignee.name')
                    ->label('Assigne a')
                    ->placeholder('-')
                    ->searchable(),

                BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'planned' => 'Planifie',
                        'confirmed' => 'Confirme',
                        'effective' => 'Effectue',
                        'cancelled' => 'Annule',
                        'rescheduled' => 'Reprogramme',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'planned',
                        'info' => 'confirmed',
                        'success' => 'effective',
                        'danger' => 'cancelled',
                        'warning' => 'rescheduled',
                    ])
                    ->sortable(),

                TextColumn::make('createdBy.name')
                    ->label('Cree par')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'planned' => 'Planifie',
                        'confirmed' => 'Confirme',
                        'effective' => 'Effectue',
                        'cancelled' => 'Annule',
                        'rescheduled' => 'Reprogramme',
                    ]),

                SelectFilter::make('assigned_to')
                    ->label('Assigne a')
                    ->relationship('assignee', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('created_by')
                    ->label('Cree par')
                    ->relationship('creator', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('mes_rendez_vous')
                    ->label('Mes rendez-vous')
                    ->query(fn (Builder $query): Builder => $query->where(function (Builder $sub): void {
                        $sub->where('assigned_to', Auth::id())
                            ->orWhere('created_by', Auth::id());
                    }))
                    ->toggle(),

                Filter::make('a_venir_7_jours')
                    ->label('A venir (7 jours)')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereBetween('starts_at', [now(), now()->copy()->addDays(7)])
                    )
                    ->toggle(),

                Filter::make('en_retard')
                    ->label('En retard')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('starts_at', '<', now())
                        ->whereIn('status', ['planned', 'confirmed', 'rescheduled'])
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
                            ->when($data['from'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('starts_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('starts_at', '<=', $date));
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
            ->defaultSort('starts_at', 'desc');
    }
}
