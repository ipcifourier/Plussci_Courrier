<?php

namespace App\Filament\Resources\Courriers\Tables;

use App\Models\User;
use App\Services\AuditLogger;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class CourriersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Entrant' => 'success',
                        'Sortant' => 'warning',
                    })
                    ->sortable(),

                TextColumn::make('canal')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Email'   => 'info',
                        'Portail' => 'primary',
                        'Fax'     => 'warning',
                        default   => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('nature_courrier')
                    ->label('Nature')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('objet')
                    ->searchable(['objet', 'ocr_text']) // C6 — include OCR text in search
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),

                TextColumn::make('collaboration_enabled')
                    ->label('Co-édition')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state ? 'Oui' : 'Non')
                    ->color(fn ($state): string => $state ? 'success' : 'gray'),

                TextColumn::make('cloud_links')
                    ->label('Liens cloud')
                    ->formatStateUsing(fn ($state) =>
                        collect($state)->map(fn ($link) => $link['url'] ?? null)->filter()->implode(", ")
                    )
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('date_reception_envoi')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('delai_reponse')
                    ->label('Délai réponse')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->color(fn ($record): string => $record?->isEnRetard() ? 'danger' : 'gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('scan_status')
                    ->label('Numérisation')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Numérisé'     => 'success',
                        'En cours'     => 'warning',
                        default        => 'danger',
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('priorite')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Urgente' => 'danger',
                        default   => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'En cours' => 'warning',
                        'Traité'   => 'success',
                        'Archivé'  => 'gray',
                        default    => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('approval_status')
                    ->label('Validation')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'not_required' => 'Non requis',
                        'pending'      => 'En attente',
                        'approved'     => 'Approuvé',
                        'rejected'     => 'Rejeté',
                        default        => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending'  => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default    => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('current_approval_level')
                    ->label('Niveau en cours')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('signed_at')
                    ->label('Signature')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => filled($state) ? 'Signé' : 'Non signé')
                    ->color(fn ($state): string => filled($state) ? 'success' : 'gray'),

                TextColumn::make('signer.name')
                    ->label('Signé par')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('signed_at')
                    ->label('Date signature')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('niveau_confidentialite')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Confidentiel' => 'warning',
                        'Personnel'    => 'danger',
                        default        => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('correspondant.nom_structure')
                    ->label('Correspondant')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('initiateur.name')
                    ->label('Agent')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('pieces_jointes_count')
                    ->label('Fichiers')
                    ->getStateUsing(fn ($record): int => $record->getMedia('pieces_jointes')->count())
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'primary' : 'gray')
                    ->formatStateUsing(fn (int $state): string => $state . ' fichier' . ($state > 1 ? 's' : ''))
                    ->icon('heroicon-o-paper-clip'),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'Entrant' => 'Entrant',
                        'Sortant' => 'Sortant',
                    ]),

                SelectFilter::make('canal')
                    ->options([
                        'Physique' => 'Physique',
                        'Email'    => 'Email',
                        'Portail'  => 'Portail',
                        'Fax'      => 'Fax',
                    ]),

                SelectFilter::make('nature_courrier')
                    ->label('Nature')
                    ->options([
                        'Lettre'          => 'Lettre',
                        'Note de service' => 'Note de service',
                        'Circulaire'      => 'Circulaire',
                        'Décision'        => 'Décision',
                        'Rapport'         => 'Rapport',
                        'Facture'         => 'Facture',
                        'Demande'         => 'Demande',
                        'Autre'           => 'Autre',
                    ]),

                SelectFilter::make('scan_status')
                    ->label('Numérisation')
                    ->options([
                        'Non numérisé' => 'Non numérisé',
                        'En cours'     => 'En cours',
                        'Numérisé'     => 'Numérisé',
                    ]),

                SelectFilter::make('priorite')
                    ->options([
                        'Normale' => 'Normale',
                        'Urgente' => 'Urgente',
                    ]),

                SelectFilter::make('statut')
                    ->options([
                        'Nouveau' => 'Nouveau',
                        'En cours' => 'En cours',
                        'Traité' => 'Traité',
                        'Archivé' => 'Archivé',
                    ]),

                SelectFilter::make('approval_status')
                    ->label('Validation')
                    ->options([
                        'not_required' => 'Non requis',
                        'pending' => 'En attente',
                        'approved' => 'Approuvé',
                        'rejected' => 'Rejeté',
                    ]),

                SelectFilter::make('niveau_confidentialite')
                    ->options([
                        'Standard' => 'Standard',
                        'Confidentiel' => 'Confidentiel',
                        'Personnel' => 'Personnel',
                    ]),

                SelectFilter::make('correspondant_id')
                    ->label('Correspondant')
                    ->relationship('correspondant', 'nom_structure')
                    ->searchable()
                    ->preload(),

                Filter::make('date_reception_envoi')
                    ->form([
                        Forms\Components\DatePicker::make('date_debut'),
                        Forms\Components\DatePicker::make('date_fin'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_debut'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date_reception_envoi', '>=', $date),
                            )
                            ->when(
                                $data['date_fin'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date_reception_envoi', '<=', $date),
                            );
                    }),

                Filter::make('en_retard')
                    ->label('En retard')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('delai_reponse')
                        ->whereDate('delai_reponse', '<', now())
                        ->whereNotIn('statut', ['Traité', 'Archivé'])),

                Filter::make('signed_only')
                    ->label('Signés uniquement')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('signed_at')),

                Filter::make('accuse_non_recu')
                    ->label('Accusé non envoyé')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('type', 'Entrant')
                        ->where('accuse_reception', false)),
            ])
            ->actions([
                Action::make('marquer_numerise')
                    ->label('Numériser')
                    ->icon('heroicon-o-camera')
                    ->color('info')
                    ->visible(fn ($record): bool => $record->canal === 'Physique'
                        && $record->scan_status !== 'Numérisé')
                    ->form([
                        Forms\Components\Select::make('scan_status')
                            ->label('Nouveau statut')
                            ->options([
                                'En cours'  => 'En cours',
                                'Numérisé'  => 'Numérisé',
                            ])
                            ->default('Numérisé')
                            ->required()
                            ->native(false),
                        Forms\Components\DatePicker::make('date_numerisation')
                            ->label('Date de numérisation')
                            ->default(now())
                            ->required(),
                    ])
                    ->action(function ($record, array $data): void {
                        $before = $record->only(['scan_status', 'date_numerisation', 'numerise_par']);

                        $record->update([
                            'scan_status'       => $data['scan_status'],
                            'date_numerisation' => $data['date_numerisation'],
                            'numerise_par'      => Auth::id(),
                        ]);

                        app(AuditLogger::class)->log(
                            action: 'courriers.numerisation',
                            entity: $record,
                            before: $before,
                            after: $record->fresh()->only(['scan_status', 'date_numerisation', 'numerise_par']),
                        );

                        Notification::make()
                            ->title($data['scan_status'] === 'Numérisé' ? 'Courrier numérisé' : 'Numérisation en cours')
                            ->success()
                            ->send();
                    }),

                Action::make('accuser_reception')
                    ->label('Accuser réception')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->visible(fn ($record): bool => $record->type === 'Entrant'
                        && ! $record->accuse_reception)
                    ->requiresConfirmation()
                    ->action(function ($record): void {
                        $record->update([
                            'accuse_reception' => true,
                            'date_accuse'      => now(),
                        ]);

                        Notification::make()
                            ->title('Accusé de réception enregistré')
                            ->success()
                            ->send();
                    }),

                Action::make('signer_sortant')
                    ->label('Signer')
                    ->icon('heroicon-o-pencil-square')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('signature_comment')
                            ->label('Commentaire de signature')
                            ->placeholder('Optionnel')
                            ->nullable(),
                    ])
                    ->visible(function ($record): bool {
                        $user = Auth::user();

                        if (! $user instanceof User) {
                            return false;
                        }

                        return $user->can('courriers.sign')
                            && $record->canBeSigned();
                    })
                    ->action(function ($record, array $data): void {
                        $user = Auth::user();

                        if (! $user instanceof User || ! $user->can('courriers.sign') || ! $record->canBeSigned()) {
                            abort(403);
                        }

                        $before = $record->only(['signed_by', 'signed_at', 'signature_comment', 'statut']);

                        $record->update([
                            'signed_by' => $user->id,
                            'signed_at' => now(),
                            'signature_comment' => $data['signature_comment'] ?? null,
                        ]);

                        $record->notifyInitiatorSigned($user);

                        app(AuditLogger::class)->log(
                            action: 'courriers.sign',
                            entity: $record,
                            before: $before,
                            after: $record->fresh()->only(['signed_by', 'signed_at', 'signature_comment', 'statut']),
                            meta: [
                                'signature_comment' => $data['signature_comment'] ?? null,
                            ],
                        );

                        Notification::make()
                            ->title('Courrier sortant signé')
                            ->success()
                            ->send();
                    }),
                Action::make('soumettre_validation')
                    ->label('Soumettre')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => $record->requires_approval && in_array($record->approval_status, ['not_required', 'rejected'], true))
                    ->action(function ($record): void {
                        $firstLevel = $record->approvals()->min('level');

                        if (! $firstLevel) {
                            Notification::make()
                                ->title('Aucun approbateur configuré')
                                ->danger()
                                ->send();

                            return;
                        }

                        $before = $record->only(['approval_status', 'current_approval_level', 'statut']);

                        $record->update([
                            'approval_status' => 'pending',
                            'current_approval_level' => $firstLevel,
                            'statut' => 'En cours',
                        ]);

                        $record->approvals()->update(['status' => 'pending', 'comment' => null, 'decided_at' => null]);
                        $record->notifyCurrentApprovers();

                        app(AuditLogger::class)->log(
                            action: 'courriers.approval.submit',
                            entity: $record,
                            before: $before,
                            after: $record->fresh()->only(['approval_status', 'current_approval_level', 'statut']),
                            meta: [
                                'first_level' => $firstLevel,
                            ],
                        );

                        Notification::make()
                            ->title('Circuit lancé')
                            ->success()
                            ->send();
                    }),
                Action::make('approuver_niveau')
                    ->label('Approuver')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(function ($record): bool {
                        if ($record->approval_status !== 'pending' || ! $record->current_approval_level) {
                            return false;
                        }

                        return $record->approvals()
                            ->where('level', $record->current_approval_level)
                            ->where('approver_id', Auth::id())
                            ->where('status', 'pending')
                            ->exists();
                    })
                    ->action(function ($record): void {
                        $approval = $record->approvals()
                            ->where('level', $record->current_approval_level)
                            ->where('approver_id', Auth::id())
                            ->where('status', 'pending')
                            ->first();

                        if (! $approval) {
                            return;
                        }

                        $before = $record->only(['approval_status', 'current_approval_level', 'statut']);

                        $approval->update([
                            'status' => 'approved',
                            'decided_at' => now(),
                        ]);

                        $remainingCurrentLevel = $record->approvals()
                            ->where('level', $record->current_approval_level)
                            ->where('status', 'pending')
                            ->count();

                        if ($remainingCurrentLevel > 0) {
                            Notification::make()
                                ->title('Approbation enregistrée')
                                ->success()
                                ->send();

                            return;
                        }

                        $nextLevel = $record->approvals()
                            ->where('status', 'pending')
                            ->min('level');

                        if ($nextLevel) {
                            $record->update(['current_approval_level' => $nextLevel]);
                            $record->notifyCurrentApprovers();
                        } else {
                            $record->update([
                                'approval_status' => 'approved',
                                'current_approval_level' => null,
                                'statut' => 'Traité',
                            ]);

                            $record->notifyInitiatorDecision('approved');
                        }

                        app(AuditLogger::class)->log(
                            action: 'courriers.approval.approve',
                            entity: $record,
                            before: $before,
                            after: $record->fresh()->only(['approval_status', 'current_approval_level', 'statut']),
                            meta: [
                                'approved_level' => $approval->level,
                                'approver_id' => Auth::id(),
                            ],
                        );

                        Notification::make()
                            ->title('Approbation enregistrée')
                            ->success()
                            ->send();
                    }),
                Action::make('rejeter_niveau')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(function ($record): bool {
                        if ($record->approval_status !== 'pending' || ! $record->current_approval_level) {
                            return false;
                        }

                        return $record->approvals()
                            ->where('level', $record->current_approval_level)
                            ->where('approver_id', Auth::id())
                            ->where('status', 'pending')
                            ->exists();
                    })
                    ->form([
                        Forms\Components\Textarea::make('comment')
                            ->label('Motif du rejet')
                            ->required(),
                    ])
                    ->action(function ($record, array $data): void {
                        $approval = $record->approvals()
                            ->where('level', $record->current_approval_level)
                            ->where('approver_id', Auth::id())
                            ->where('status', 'pending')
                            ->first();

                        if (! $approval) {
                            return;
                        }

                        $before = $record->only(['approval_status', 'current_approval_level', 'statut']);

                        $approval->update([
                            'status' => 'rejected',
                            'comment' => $data['comment'],
                            'decided_at' => now(),
                        ]);

                        $record->update([
                            'approval_status' => 'rejected',
                            'current_approval_level' => null,
                        ]);

                        $record->notifyInitiatorDecision('rejected', $data['comment']);

                        app(AuditLogger::class)->log(
                            action: 'courriers.approval.reject',
                            entity: $record,
                            before: $before,
                            after: $record->fresh()->only(['approval_status', 'current_approval_level', 'statut']),
                            meta: [
                                'rejected_level' => $approval->level,
                                'approver_id' => Auth::id(),
                                'comment' => $data['comment'],
                            ],
                        );

                        Notification::make()
                            ->title('Courrier rejeté')
                            ->danger()
                            ->send();
                    }),
                Action::make('demarrer_traitement')
                    ->label('Démarrer')
                    ->icon('heroicon-o-play')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => $record->statut === 'Nouveau' && ! $record->requires_approval)
                    ->action(function ($record): void {
                        $record->update(['statut' => 'En cours']);

                        Notification::make()
                            ->title('Traitement démarré')
                            ->success()
                            ->send();
                    }),
                Action::make('valider_courrier')
                    ->label('Valider')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => $record->statut === 'En cours')
                    ->action(function ($record): void {
                        $record->update(['statut' => 'Traité']);

                        Notification::make()
                            ->title('Courrier validé')
                            ->success()
                            ->send();
                    }),
                Action::make('archiver_courrier')
                    ->label('Archiver')
                    ->icon('heroicon-o-archive-box')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => $record->statut === 'Traité')
                    ->action(function ($record): void {
                        $record->update(['statut' => 'Archivé']);

                        Notification::make()
                            ->title('Courrier archivé')
                            ->success()
                            ->send();
                    }),
                Action::make('voir_modal')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record): string => 'Lecture du courrier ' . $record->reference)
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer')
                    ->modalContent(fn ($record) => view('filament.modals.media-preview', [
                        'mediaItems' => $record->getMedia('pieces_jointes'),
                        'emptyMessage' => 'Aucune piece jointe a afficher pour ce courrier.',
                    ])),
                // Actions de co-édition collaborative
                Action::make('activer_coedition')
                    ->label('Activer co-édition')
                    ->icon('heroicon-o-users')
                    ->color('success')
                    ->visible(function ($record): bool {
                        $user = Auth::user();
                        return $user instanceof User
                            && $user->can('courriers.coedit')
                            && ! (bool) $record->collaboration_enabled;
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Activer la co-édition')
                    ->modalDescription('Permettre la modification simultanée du courrier par plusieurs utilisateurs.')
                    ->action(function ($record): void {
                        $record->update([
                            'collaboration_enabled' => true,
                        ]);
                        app(AuditLogger::class)->log(
                            action: 'courriers.collaboration.enable',
                            entity: $record,
                            meta: ['enabled_by' => Auth::id()],
                        );
                        Notification::make()
                            ->title('Co-édition activée')
                            ->success()
                            ->send();
                    }),

                Action::make('finaliser_lecture_seule')
                    ->label('Finaliser (lecture seule)')
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->visible(function ($record): bool {
                        $user = Auth::user();
                        return $user instanceof User
                            && $user->can('courriers.coedit')
                            && (bool) $record->collaboration_enabled;
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Finaliser le courrier')
                    ->modalDescription('Le courrier passera en lecture seule jusqu’à déverrouillage administrateur.')
                    ->action(function ($record): void {
                        $record->update([
                            'collaboration_enabled' => false,
                        ]);
                        app(AuditLogger::class)->log(
                            action: 'courriers.collaboration.finalize_read_only',
                            entity: $record,
                            meta: ['finalized_by' => Auth::id()],
                        );
                        Notification::make()
                            ->title('Courrier finalisé en lecture seule')
                            ->success()
                            ->send();
                    }),

                Action::make('coedit_office')
                    ->label('Coéditer (OnlyOffice)')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->visible(function ($record): bool {
                        $user = Auth::user();
                        return $user instanceof User
                            && (bool) $record->collaboration_enabled;
                    })
                    ->url(fn ($record): string => route('onlyoffice.editor', ['document' => $record->id]))
                    ->openUrlInNewTab(),

                EditAction::make()
                    ->visible(function ($record) {
                        $user = Auth::user();
                        if (! $user instanceof \App\Models\User) {
                            return false;
                        }
                        // Restrict edit to GTT responsible or admin
                        $isAdmin = $user->hasRole('Super Admin') || $user->can('admin.roles.manage');
                        $isResponsible = $record->initiateur && $record->initiateur->gtt && $record->initiateur->gtt->responsable == $user->id;
                        return $isAdmin || $isResponsible;
                    }),
                DeleteAction::make()
                    ->visible(function ($record) {
                        $user = Auth::user();
                        if (! $user instanceof \App\Models\User) {
                            return false;
                        }
                        // Restrict delete to GTT responsible or admin
                        $isAdmin = $user->hasRole('Super Admin') || $user->can('admin.roles.manage');
                        $isResponsible = $record->initiateur && $record->initiateur->gtt && $record->initiateur->gtt->responsable == $user->id;
                        return $isAdmin || $isResponsible;
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}