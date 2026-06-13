<?php

namespace App\Filament\Resources\Documents\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Models\DocumentSignature;
use App\Models\DocumentWorkflowStep;
use App\Models\DocumentType;
use App\Models\Dossier;
use App\Models\Gtt;
use App\Models\InterventionDomain;
use App\Models\InterventionSubdomain;
use App\Models\User;
use App\Services\ArchiveService;
use App\Services\AuditLogger;
use App\Services\DocumentWorkflowService;
use App\Services\OnlyOfficeService;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_doc')
                    ->label('Référence')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('titre')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->dossier?->libelle ? '📁 '.$record->dossier->libelle : null)
                    ->limit(45),

                BadgeColumn::make('type_document')
                    ->label('Type')
                    ->searchable()
                    ->sortable(),
                // Tags column — shows first 3 tags as coloured chips
                TextColumn::make('tags_json')
                    ->label('Tags')
                    ->formatStateUsing(function ($state): string {
                        if (empty($state)) {
                            return '';
                        }
                        $tags = is_array($state) ? $state : json_decode($state, true) ?? [];
                        return implode(', ', array_slice($tags, 0, 3))
                            . (count($tags) > 3 ? ' +' . (count($tags) - 3) : '');
                    })
                    ->badge()
                    ->color('info')
                    ->placeholder('\u2014')
                    ->toggleable(),

                TextColumn::make('cloud_links')
                    ->label('Liens cloud')
                    ->formatStateUsing(fn ($state) => collect($state)->map(fn ($link) => $link['url'] ?? null)->filter()->implode(", "))
                    ->toggleable(isToggledHiddenByDefault: false),
                BadgeColumn::make('etat_cycle_vie')
                    ->label('État')
                    ->sortable()
                    ->colors([
                        'warning' => 'Brouillon',
                        'success' => 'Valide',
                        'gray'    => 'Archive',
                    ]),

                BadgeColumn::make('parapheur_status')
                    ->label('Parapheur')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'not_required' => '—',
                        'pending'      => 'En circuit',
                        'completed'    => 'Signé',
                        'rejected'     => 'Rejeté',
                        default        => $state,
                    })
                    ->colors([
                        'gray'    => 'not_required',
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger'  => 'rejected',
                    ])
                    ->sortable()
                    ->toggleable(),

                BadgeColumn::make('collaboration_status')
                    ->label('Collaboration')
                    ->state(fn ($record): string => match (true) {
                        $record->finalized_read_only_at !== null => 'Finalisé (lecture seule)',
                        (bool) $record->collaboration_enabled => 'Co-édition active',
                        default => 'Standard',
                    })
                    ->colors([
                        'danger' => 'Finalisé (lecture seule)',
                        'success' => 'Co-édition active',
                        'gray' => 'Standard',
                    ])
                    ->toggleable(),

                TextColumn::make('auteur.name')
                    ->label('Auteur')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('courrier.reference')
                    ->label('Courrier lié')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('interventionDomain.name')
                    ->label('Domaine')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('interventionSubdomain.name')
                    ->label('Sous-domaine')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('gtt.name')
                    ->label('GTT')
                    ->placeholder('—')
                    ->toggleable(),

                BadgeColumn::make('confidentiality_level')
                    ->label('Confidentialité')
                    ->sortable()
                    ->colors([
                        'gray'    => 'Standard',
                        'warning' => 'Confidentiel',
                        'danger'  => 'Personnel',
                    ])
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i'),

                TextColumn::make('cloud_links')
                    ->label('Liens cloud')
                    ->formatStateUsing(fn ($state) => collect($state)->map(fn ($link) => $link['url'] ?? null)->filter()->implode(", "))
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('media_count')
                    ->label('Fichiers')
                    ->counts('media')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray')
                    ->icon('heroicon-m-paper-clip')
                    ->sortable(),

                IconColumn::make('locked_by')
                    ->label('')
                    ->tooltip(fn ($record): string => $record->locked_by
                        ? 'Verrouillé par ' . ($record->lockedBy?->name ?? '…')
                        : '')
                    ->icon(fn ($record): string => $record->locked_by ? 'heroicon-m-lock-closed' : '')
                    ->color('warning')
                    ->toggleable(isToggledHiddenByDefault: false),
            ])

            ->filters([
                SelectFilter::make('type_document')
                    ->label('Type de document')
                    ->options(fn () => DocumentType::query()->orderBy('name')->pluck('name', 'name')->toArray())
                    ->multiple()
                    ->searchable(),

                SelectFilter::make('etat_cycle_vie')
                    ->label('État du cycle de vie')
                    ->options([
                        'Brouillon' => 'Brouillon',
                        'Valide'    => 'Validé',
                        'Archive'   => 'Archivé',
                    ])
                    ->multiple(),

                SelectFilter::make('confidentiality_level')
                    ->label('Confidentialité')
                    ->options([
                        'Standard'     => 'Standard',
                        'Confidentiel' => 'Confidentiel',
                        'Personnel'    => 'Personnel',
                    ])
                    ->multiple(),

                SelectFilter::make('auteur_id')
                    ->label('Auteur')
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable(),

                SelectFilter::make('dossier_id')
                    ->label('Dossier GED')
                    ->options(fn () => Dossier::orderBy('libelle')->pluck('libelle', 'id')->toArray())
                    ->searchable(),

                SelectFilter::make('intervention_domain_id')
                    ->label('Domaine d\'intervention')
                    ->options(fn () => InterventionDomain::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable(),

                SelectFilter::make('intervention_subdomain_id')
                    ->label('Sous-domaine')
                    ->options(fn () => InterventionSubdomain::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable(),

                SelectFilter::make('gtt_id')
                    ->label('GTT')
                    ->options(fn () => Gtt::query()->visibleTo(Auth::user())->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable(),

                Filter::make('mes_documents')
                    ->label('Mes documents')
                    ->query(fn (Builder $query) => $query->where('auteur_id', Auth::id()))
                    ->toggle(),

                Filter::make('a_signer')
                    ->label('À signer par moi')
                    ->query(fn (Builder $query) => $query
                        ->where('parapheur_status', 'pending')
                        ->whereHas('signatures', fn ($q) => $q
                            ->where('signataire_id', Auth::id())
                            ->where('status', 'pending')
                            ->whereColumn('level', 'documents.current_signature_level')
                        )
                    )
                    ->toggle(),

                SelectFilter::make('parapheur_status')
                    ->label('Statut parapheur')
                    ->options([
                        'not_required' => 'Sans circuit',
                        'pending'      => 'En circuit',
                        'completed'    => 'Signé',
                        'rejected'     => 'Rejeté',
                    ]),

                Filter::make('avec_courrier')
                    ->label('Avec courrier lié')
                    ->query(fn (Builder $query) => $query->whereNotNull('courrier_id'))
                    ->toggle(),

                Filter::make('created_at')
                    ->label('Période de création')
                    ->form([
                        DatePicker::make('from')->label('Du')->native(false),
                        DatePicker::make('until')->label('Au')->native(false),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'],  fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']));
                    }),
            ])

            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContentCollapsible)

            ->actions([
                Action::make('valider_workflow')
                    ->label('Valider')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(function ($record): bool {
                        return DocumentWorkflowStep::query()
                            ->where('approver_id', Auth::id())
                            ->where('status', 'pending')
                            ->whereHas('workflow', fn (Builder $query) => $query
                                ->where('document_id', $record->id)
                                ->where('status', 'pending')
                                ->whereColumn('document_workflow_steps.step_order', 'document_workflows.current_step_order'))
                            ->exists();
                    })
                    ->form([
                        Textarea::make('comment')
                            ->label('Commentaire (optionnel)')
                            ->rows(2)
                            ->nullable(),
                    ])
                    ->action(function ($record, array $data): void {
                        $step = DocumentWorkflowStep::query()
                            ->where('approver_id', Auth::id())
                            ->where('status', 'pending')
                            ->whereHas('workflow', fn (Builder $query) => $query
                                ->where('document_id', $record->id)
                                ->where('status', 'pending')
                                ->whereColumn('document_workflow_steps.step_order', 'document_workflows.current_step_order'))
                            ->oldest('created_at')
                            ->first();

                        if (! $step || ! Auth::user()) {
                            Notification::make()
                                ->title('Aucune validation en attente pour vous')
                                ->warning()
                                ->send();

                            return;
                        }

                        app(DocumentWorkflowService::class)->approve($step, Auth::user(), $data['comment'] ?? null);

                        app(AuditLogger::class)->log(
                            action: 'documents.workflow.approve',
                            entity: $record,
                            meta: ['step_id' => $step->id, 'approver_id' => Auth::id()],
                        );

                        Notification::make()
                            ->title('Étape validée')
                            ->success()
                            ->send();
                    }),

                Action::make('rejeter_workflow')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(function ($record): bool {
                        return DocumentWorkflowStep::query()
                            ->where('approver_id', Auth::id())
                            ->where('status', 'pending')
                            ->whereHas('workflow', fn (Builder $query) => $query
                                ->where('document_id', $record->id)
                                ->where('status', 'pending')
                                ->whereColumn('document_workflow_steps.step_order', 'document_workflows.current_step_order'))
                            ->exists();
                    })
                    ->form([
                        Textarea::make('comment')
                            ->label('Motif du rejet')
                            ->required()
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data): void {
                        $step = DocumentWorkflowStep::query()
                            ->where('approver_id', Auth::id())
                            ->where('status', 'pending')
                            ->whereHas('workflow', fn (Builder $query) => $query
                                ->where('document_id', $record->id)
                                ->where('status', 'pending')
                                ->whereColumn('document_workflow_steps.step_order', 'document_workflows.current_step_order'))
                            ->oldest('created_at')
                            ->first();

                        if (! $step || ! Auth::user()) {
                            Notification::make()
                                ->title('Aucune validation en attente pour vous')
                                ->warning()
                                ->send();

                            return;
                        }

                        app(DocumentWorkflowService::class)->reject($step, Auth::user(), (string) $data['comment']);

                        app(AuditLogger::class)->log(
                            action: 'documents.workflow.reject',
                            entity: $record,
                            meta: ['step_id' => $step->id, 'approver_id' => Auth::id()],
                        );

                        Notification::make()
                            ->title('Étape rejetée')
                            ->danger()
                            ->send();
                    }),

                Action::make('coedit_office')
                    ->label('Coéditer Office')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->visible(function ($record): bool {
                        $currentUser = Auth::user();

                        return $currentUser instanceof User
                            && app(OnlyOfficeService::class)->isEnabled()
                            && app(OnlyOfficeService::class)->getPrimaryOfficeMedia($record) !== null;
                    })
                    ->url(fn ($record): string => route('onlyoffice.editor', ['document' => $record->id]))
                    ->openUrlInNewTab(),

                Action::make('activer_coedition')
                    ->label('Activer co-édition')
                    ->icon('heroicon-o-users')
                    ->color('success')
                    ->visible(function ($record): bool {
                        $currentUser = Auth::user();

                        return $currentUser instanceof User
                            && $currentUser->can('update', $record)
                            && ! (bool) $record->collaboration_enabled;
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Activer la co-édition')
                    ->modalDescription('Plusieurs utilisateurs autorisés pourront modifier ce document en parallèle.')
                    ->action(function ($record): void {
                        $record->update([
                            'collaboration_enabled' => true,
                            'finalized_read_only_at' => null,
                            'finalized_read_only_by' => null,
                        ]);

                        app(AuditLogger::class)->log(
                            action: 'documents.collaboration.enable',
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
                        $currentUser = Auth::user();

                        return $currentUser instanceof User
                            && $currentUser->can('update', $record)
                            && (bool) $record->collaboration_enabled
                            && $record->finalized_read_only_at === null;
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Finaliser le document')
                    ->modalDescription('Le document passera en lecture seule jusqu\'à déverrouillage administrateur.')
                    ->action(function ($record): void {
                        $record->update([
                            'finalized_read_only_at' => now(),
                            'finalized_read_only_by' => Auth::id(),
                            'locked_by' => null,
                            'locked_at' => null,
                        ]);

                        \App\Models\DocumentSession::query()
                            ->where('document_id', $record->id)
                            ->where('mode', 'edit')
                            ->update(['mode' => 'view']);

                        app(AuditLogger::class)->log(
                            action: 'documents.collaboration.finalize_read_only',
                            entity: $record,
                            meta: ['finalized_by' => Auth::id()],
                        );

                        Notification::make()
                            ->title('Document finalisé en lecture seule')
                            ->success()
                            ->send();
                    }),

                Action::make('deverrouiller_finalisation')
                    ->label('Déverrouiller (admin)')
                    ->icon('heroicon-o-lock-open')
                    ->color('danger')
                    ->visible(function ($record): bool {
                        $currentUser = Auth::user();

                        return $record->finalized_read_only_at !== null
                            && $currentUser instanceof User
                            && ($currentUser->can('admin.roles.manage') || $currentUser->hasRole('Super Admin'));
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Déverrouiller la finalisation')
                    ->modalDescription('Le document redeviendra modifiable en co-édition.')
                    ->action(function ($record): void {
                        $record->update([
                            'collaboration_enabled' => true,
                            'finalized_read_only_at' => null,
                            'finalized_read_only_by' => null,
                        ]);

                        app(AuditLogger::class)->log(
                            action: 'documents.collaboration.unlock',
                            entity: $record,
                            meta: ['unlocked_by' => Auth::id()],
                        );

                        Notification::make()
                            ->title('Finalisation déverrouillée')
                            ->success()
                            ->send();
                    }),

                // ── Parapheur : lancer le circuit ────────────────────────────
                Action::make('initier_parapheur')
                    ->label('Lancer le parapheur')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->visible(fn ($record): bool =>
                        $record->parapheur_status === 'not_required'
                        && $record->signatures()->count() > 0
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Lancer le circuit de signature')
                    ->modalDescription('Le document sera envoyé aux signataires configurés, dans l\'ordre défini.')
                    ->action(function ($record): void {
                        $record->lancerParapheur();

                        app(AuditLogger::class)->log(
                            action: 'documents.parapheur.launch',
                            entity: $record,
                            meta: ['first_level' => $record->fresh()->current_signature_level],
                        );

                        Notification::make()
                            ->title('Circuit de parapheur lancé')
                            ->body('Les signataires ont été notifiés.')
                            ->success()
                            ->send();
                    }),

                // ── Parapheur : signer depuis la liste ───────────────────────
                Action::make('signer_document')
                    ->label('Signer')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(function ($record): bool {
                        if ($record->parapheur_status !== 'pending') {
                            return false;
                        }

                        return DocumentSignature::query()
                            ->where('document_id', $record->id)
                            ->where('level', $record->current_signature_level)
                            ->where('signataire_id', Auth::id())
                            ->where('status', 'pending')
                            ->exists();
                    })
                    ->form([
                        Textarea::make('comment')
                            ->label('Commentaire (optionnel)')
                            ->rows(2)
                            ->nullable(),
                    ])
                    ->action(function ($record, array $data): void {
                        $sig = DocumentSignature::query()
                            ->where('document_id', $record->id)
                            ->where('level', $record->current_signature_level)
                            ->where('signataire_id', Auth::id())
                            ->where('status', 'pending')
                            ->first();

                        if (! $sig) {
                            return;
                        }

                        $sig->update([
                            'status'     => 'signed',
                            'comment'    => $data['comment'] ?? null,
                            'signed_at'  => now(),
                            'ip_address' => request()->ip(),
                        ]);

                        app(AuditLogger::class)->log(
                            action: 'documents.parapheur.sign',
                            entity: $record,
                            meta: ['level' => $sig->level, 'signataire_id' => Auth::id()],
                        );

                        $pendingAtLevel = $record->signatures()
                            ->where('level', $record->current_signature_level)
                            ->where('status', 'pending')
                            ->count();

                        if ($pendingAtLevel === 0) {
                            $nextLevel = $record->signatures()
                                ->where('status', 'pending')
                                ->min('level');

                            if ($nextLevel) {
                                $record->update(['current_signature_level' => $nextLevel]);
                                $record->notifyCurrentSignataires();
                            } else {
                                $record->update([
                                    'parapheur_status'        => 'completed',
                                    'current_signature_level' => null,
                                    'etat_cycle_vie'          => 'Valide',
                                ]);
                                $record->notifyAuteurDecision('completed');
                            }
                        }

                        Notification::make()->title('Signature enregistrée')->success()->send();
                    }),

                // ── Parapheur : rejeter depuis la liste ──────────────────────
                Action::make('rejeter_signature')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(function ($record): bool {
                        if ($record->parapheur_status !== 'pending') {
                            return false;
                        }

                        return DocumentSignature::query()
                            ->where('document_id', $record->id)
                            ->where('level', $record->current_signature_level)
                            ->where('signataire_id', Auth::id())
                            ->where('status', 'pending')
                            ->exists();
                    })
                    ->form([
                        Textarea::make('comment')
                            ->label('Motif du rejet')
                            ->required()
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data): void {
                        $sig = DocumentSignature::query()
                            ->where('document_id', $record->id)
                            ->where('level', $record->current_signature_level)
                            ->where('signataire_id', Auth::id())
                            ->where('status', 'pending')
                            ->first();

                        if (! $sig) {
                            return;
                        }

                        $sig->update([
                            'status'    => 'rejected',
                            'comment'   => $data['comment'],
                            'signed_at' => now(),
                        ]);

                        $record->update([
                            'parapheur_status'        => 'rejected',
                            'current_signature_level' => null,
                        ]);

                        $record->notifyAuteurDecision('rejected', $data['comment']);

                        app(AuditLogger::class)->log(
                            action: 'documents.parapheur.reject',
                            entity: $record,
                            meta: ['level' => $sig->level, 'signataire_id' => Auth::id()],
                        );

                        Notification::make()->title('Circuit rejeté')->danger()->send();
                    }),

                Action::make('download')
                    ->label('Télécharger')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn ($record): bool => $record->hasMedia('documents'))
                    ->url(function ($record): string {
                        $media = $record->getMedia('documents');

                        if ($media->count() === 1) {
                            return $media->first()->getUrl();
                        }

                        return \App\Filament\Resources\Documents\DocumentResource::getUrl('view', ['record' => $record]);
                    })
                    ->openUrlInNewTab(),

                Action::make('voir_modal')
                    ->label('Aperçu')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record): string => 'Lecture du document ' . ($record->reference_doc ?? ('#' . $record->id)))
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer')
                    ->modalContent(fn ($record) => view('filament.modals.media-preview', [
                        'mediaItems' => $record->getMedia('documents'),
                        'emptyMessage' => 'Aucun fichier a afficher pour ce document.',
                    ])),

                Action::make('open_record_view')
                    ->label('Détails & partage')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record): string => \App\Filament\Resources\Documents\DocumentResource::getUrl('view', ['record' => $record])) ,
                EditAction::make()
                    ->visible(function ($record) {
                        $user = Auth::user();
                        return $user && ($user instanceof \App\Models\User && ($user->hasRole('Super Admin') || $user->hasRole('GTT Responsable')) && $record->gtt?->responsable == $user->id);
                    }),

                Action::make('archive')
                    ->label('Archiver')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Archivage électronique')
                    ->modalDescription('Ce document sera marqué comme archivé et une empreinte d\'intégrité SHA-256 sera calculée.')
                    ->form([
                        Textarea::make('reason')
                            ->label('Motif d\'archivage')
                            ->rows(2)
                            ->maxLength(500)
                            ->nullable(),
                        TextInput::make('legal_basis')
                            ->label('Base légale')
                            ->maxLength(255)
                            ->nullable(),
                        Select::make('retention_years')
                            ->label('Durée de conservation (années)')
                            ->options(array_combine(range(1, 30), array_map(fn ($y) => $y . ' an' . ($y > 1 ? 's' : ''), range(1, 30))))
                            ->default(5)
                            ->required()
                            ->native(false),
                    ])
                    ->action(function ($record, array $data): void {
                        $user = Auth::user();
                        app(ArchiveService::class)->archiveDocument(
                            document:       $record,
                            user:           $user,
                            reason:         $data['reason'] ?? '',
                            legalBasis:     $data['legal_basis'] ?? '',
                            retentionYears: (int) ($data['retention_years'] ?? 5),
                        );
                        Notification::make()
                            ->title('Document archivé')
                            ->body('Empreinte SHA-256 calculée. Conservation : ' . $data['retention_years'] . ' ans.')
                            ->success()
                            ->send();
                    })
                    ->visible(function ($record) {
                        $user = Auth::user();
                        return $record->etat_cycle_vie !== 'Archive' && $user instanceof \App\Models\User && ($user->hasRole('Super Admin') || $user->hasRole('GTT Responsable')) && $record->gtt?->responsable == $user->id;
                    }),

                DeleteAction::make()
                    ->visible(function ($record) {
                        $user = Auth::user();
                        return $user && ($user instanceof \App\Models\User && ($user->hasRole('Super Admin') || $user->hasRole('GTT Responsable')) && $record->gtt?->responsable == $user->id);
                    }),
            ])

            ->headerActions([
                Action::make('export_csv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->action(fn ($table) => $table->export('csv')),
                Action::make('export_xlsx')
                    ->label('Export XLSX')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(fn ($table) => $table->export('xlsx')),
                Action::make('recherche_intelligente')
                    ->label('Recherche Intelligente')
                    ->icon('heroicon-o-light-bulb')
                    ->color('info')
                    ->modalHeading('Recherche intelligente')
                    ->modalDescription('Utilisez des mots-clés ou des critères avancés pour filtrer les documents.')
                    ->modalSubmitActionLabel('Rechercher')
                    ->form([
                        TextInput::make('keywords')
                            ->label('Mots-clés')
                            ->placeholder('Ex : rapport, confidentiel, 2026'),
                    ])
                    ->action(function ($data, $table) {
                        // Logique de recherche avancée à adapter
                        Notification::make()
                            ->title('Recherche intelligente')
                            ->body('Recherche lancée pour : ' . ($data['keywords'] ?? ''))
                            ->success()
                            ->send();
                    }),
            ])
            ->searchPlaceholder('Recherche par référence, titre, auteur, dossier…')

            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}

