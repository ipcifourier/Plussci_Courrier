<?php

namespace App\Filament\Resources\MeetingTasks\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class MeetingTasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Diligence')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('meeting.title')
                    ->label('Reunion')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('agendaItem.title')
                    ->label('Point agenda')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('assignee.name')
                    ->label('Assigne a')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('due_at')
                    ->label('Echeance')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-')
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'todo' => 'A faire',
                        'in_progress' => 'En cours',
                        'done' => 'Terminee',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'todo',
                        'warning' => 'in_progress',
                        'success' => 'done',
                    ])
                    ->sortable(),

                BadgeColumn::make('priority')
                    ->label('Priorite')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'low' => 'Basse',
                        'normal' => 'Normale',
                        'high' => 'Haute',
                        'urgent' => 'Urgente',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'low',
                        'info' => 'normal',
                        'warning' => 'high',
                        'danger' => 'urgent',
                    ])
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('meeting_id')
                    ->label('Reunion')
                    ->relationship('meeting', 'title')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'todo' => 'A faire',
                        'in_progress' => 'En cours',
                        'done' => 'Terminee',
                    ]),

                SelectFilter::make('priority')
                    ->label('Priorite')
                    ->options([
                        'low' => 'Basse',
                        'normal' => 'Normale',
                        'high' => 'Haute',
                        'urgent' => 'Urgente',
                    ]),

                SelectFilter::make('assigned_to')
                    ->label('Assigne a')
                    ->relationship('assignee', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('mes_diligences')
                    ->label('Mes diligences')
                    ->query(fn (Builder $query): Builder => $query->where('assigned_to', Auth::id()))
                    ->toggle(),

                Filter::make('en_retard')
                    ->label('En retard')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('due_at')
                        ->where('due_at', '<', now())
                        ->where('status', '!=', 'done')
                    )
                    ->toggle(),

                Filter::make('echeance_du_jour')
                    ->label('Echeance du jour')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereDate('due_at', now()->toDateString())
                        ->where('status', '!=', 'done')
                    )
                    ->toggle(),

                Filter::make('periode')
                    ->label('Periode echeance')
                    ->form([
                        DatePicker::make('from')->label('Du')->native(false),
                        DatePicker::make('until')->label('Au')->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('due_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('due_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                Action::make('terminate')
                    ->label('Terminer')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record): bool => $record->status !== 'done')
                    ->requiresConfirmation()
                    ->action(function ($record): void {
                        $record->update([
                            'status' => 'done',
                        ]);

                        Notification::make()
                            ->title('Diligence terminee')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('terminate_selected')
                        ->label('Terminer la selection')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $terminated = 0;

                            foreach ($records as $record) {
                                if ($record->status === 'done') {
                                    continue;
                                }

                                $record->update([
                                    'status' => 'done',
                                ]);

                                $terminated++;
                            }

                            Notification::make()
                                ->title($terminated > 0
                                    ? "{$terminated} diligence(s) terminee(s)"
                                    : 'Aucune diligence a terminer')
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('due_at', 'asc');
    }
}
