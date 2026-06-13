<?php

namespace App\Filament\Resources\Tasks\Tables;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskDeadlineReminderNotification;
use App\Services\AuditLogger;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('titre')
                    ->label('Titre')
                    ->searchable()
                    ->sortable()
                    ->limit(60),

                TextColumn::make('assignee.name')
                    ->label('Assigné à')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('assigner.name')
                    ->label('Créé par')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                BadgeColumn::make('priority')
                    ->label('Priorité')
                    ->colors([
                        'secondary' => 'Basse',
                        'primary'   => 'Normale',
                        'warning'   => 'Haute',
                        'danger'    => 'Urgente',
                    ]),

                BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'todo'      => 'À faire',
                        'doing'     => 'En cours',
                        'done'      => 'Terminé',
                        'cancelled' => 'Annulé',
                        default     => $state,
                    })
                    ->colors([
                        'secondary' => 'todo',
                        'primary'   => 'doing',
                        'success'   => 'done',
                        'danger'    => 'cancelled',
                    ]),

                TextColumn::make('due_date')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn (Task $record): ?string => $record->isOverdue() ? 'danger' : null),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'todo'      => 'À faire',
                        'doing'     => 'En cours',
                        'done'      => 'Terminé',
                        'cancelled' => 'Annulé',
                    ]),

                SelectFilter::make('priority')
                    ->label('Priorité')
                    ->options([
                        'Basse'   => 'Basse',
                        'Normale' => 'Normale',
                        'Haute'   => 'Haute',
                        'Urgente' => 'Urgente',
                    ]),

                SelectFilter::make('assignee_id')
                    ->label('Assigné à')
                    ->relationship('assignee', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('en_retard')
                    ->label('En retard uniquement')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('due_date')
                        ->where('due_date', '<', now())
                        ->whereNotIn('status', ['done', 'cancelled'])
                    )
                    ->toggle(),
            ])
            ->actions([
                Action::make('demarrer')
                    ->label('Démarrer')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->visible(fn (Task $record): bool => $record->status === 'todo')
                    ->requiresConfirmation()
                    ->action(function (Task $record): void {
                        $audit = app(AuditLogger::class);
                        $audit->log(action: 'task.status_changed', entity: $record, before: ['status' => $record->status], after: ['status' => 'doing']);
                        $record->update(['status' => 'doing']);
                        Notification::make()->title('Tâche démarrée')->success()->send();
                    }),

                Action::make('terminer')
                    ->label('Terminer')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Task $record): bool => $record->status === 'doing')
                    ->requiresConfirmation()
                    ->action(function (Task $record): void {
                        $audit = app(AuditLogger::class);
                        $audit->log(action: 'task.status_changed', entity: $record, before: ['status' => $record->status], after: ['status' => 'done']);
                        $record->update(['status' => 'done']);
                        Notification::make()->title('Tâche terminée')->success()->send();
                    }),

                Action::make('annuler')
                    ->label('Annuler')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Task $record): bool => ! in_array($record->status, ['done', 'cancelled']))
                    ->requiresConfirmation()
                    ->action(function (Task $record): void {
                        $audit = app(AuditLogger::class);
                        $audit->log(action: 'task.status_changed', entity: $record, before: ['status' => $record->status], after: ['status' => 'cancelled']);
                        $record->update(['status' => 'cancelled']);
                        Notification::make()->title('Tâche annulée')->warning()->send();
                    }),

                Action::make('relancer')
                    ->label('Relancer')
                    ->icon('heroicon-o-bell-alert')
                    ->color('warning')
                    ->visible(function (Task $record): bool {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return $record->isOverdue()
                            && $record->assignee_id !== null
                            && $user instanceof User
                            && (
                                $user->hasPermissionTo('admin.roles.manage')
                                || $user->hasPermissionTo('collaboration.tasks.assign')
                            );
                    })
                    ->action(function (Task $record): void {
                        $record->assignee?->notify(new TaskDeadlineReminderNotification($record));
                        $record->updateQuietly(['alerte_envoyee_at' => now()]);
                        Notification::make()->title('Relance envoyée')->success()->send();
                    }),

                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->visible(function (): bool {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return $user instanceof User
                            && $user->hasPermissionTo('admin.roles.manage');
                    }),
            ]);
    }
}
