<?php

namespace App\Filament\Resources\Documents\RelationManagers;

use App\Models\DocumentSession;
use App\Services\DocumentPresenceService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * Read-only RelationManager showing who is currently active on a document.
 *
 * Uses the `activeSessions` relation (TTL-scoped, 5 min) for live data.
 * Super Admin can forcefully expel a user's session.
 */
class DocumentSessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'activeSessions';

    protected static ?string $title = 'SYSGED Share - Présence';

    /**
     * No create / edit form — sessions are managed by the service, not manually.
     */
    public function isReadOnly(): bool
    {
        return false; // Allow custom header actions (force-clean)
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->heading('Utilisateurs actifs sur ce document')
            ->description('Sessions ouvertes dans les ' . DocumentPresenceService::TTL_MINUTES . ' dernières minutes. Actualisez la page pour voir l\'état en temps réel.')
            ->emptyStateHeading('Aucun utilisateur actuellement actif')
            ->emptyStateDescription('Les sessions apparaissent ici dès qu\'un utilisateur ouvre le document.')
            ->emptyStateIcon('heroicon-o-users')
            ->poll('30s')   // auto-refresh Filament table polling

            ->columns([
                TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                BadgeColumn::make('mode')
                    ->label('Mode')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'edit' => '✎ Édition',
                        'view' => '👁 Consultation',
                        default => $state,
                    })
                    ->colors([
                        'danger'  => 'edit',
                        'primary' => 'view',
                    ]),

                TextColumn::make('joined_at')
                    ->label('Arrivé à')
                    ->dateTime('H:i:s')
                    ->sortable(),

                TextColumn::make('last_seen_at')
                    ->label('Dernière activité')
                    ->dateTime('H:i:s')
                    ->sortable()
                    ->description(fn (DocumentSession $record): string =>
                        $record->last_seen_at?->diffForHumans() ?? '—'
                    ),
            ])

            ->headerActions([
                Action::make('clean_stale')
                    ->label('Purger les sessions expirées')
                    ->icon('heroicon-o-trash')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Purger les sessions fantômes ?')
                    ->modalDescription('Supprime toutes les sessions dont la dernière activité date de plus de ' . DocumentPresenceService::TTL_MINUTES . ' minutes (utilisateurs déconnectés sans fermeture propre).')
                    ->visible(function (): bool {
                        $user = Auth::user();

                        return $user instanceof \App\Models\User && $user->can('admin.roles.manage');
                    })
                    ->action(function (): void {
                        $deleted = app(DocumentPresenceService::class)->cleanStaleSessions();

                        Notification::make()
                            ->title('Sessions purgées')
                            ->body($deleted . ' session(s) expirée(s) supprimée(s).')
                            ->success()
                            ->send();
                    }),
            ])

            ->actions([
                Action::make('expel')
                    ->label('Expulser')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Expulser cet utilisateur ?')
                    ->modalDescription('Sa session de présence sera supprimée. Il sera notifié lors de sa prochaine interaction.')
                    ->visible(function (): bool {
                        $user = Auth::user();

                        return $user instanceof \App\Models\User && $user->can('admin.roles.manage');
                    })
                    ->action(function (DocumentSession $record): void {
                        $document = $this->getOwnerRecord();

                        if ($record->user) {
                            app(DocumentPresenceService::class)->leave($document, $record->user);
                        }

                        Notification::make()
                            ->title('Session supprimée')
                            ->body(($record->user?->name ?? '?') . ' a été retiré du document.')
                            ->success()
                            ->send();
                    }),
            ])

            ->defaultSort('joined_at', 'asc')
            ->striped();
    }
}
