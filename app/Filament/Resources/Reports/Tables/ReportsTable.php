<?php

namespace App\Filament\Resources\Reports\Tables;

use App\Models\ReportApproval;
use App\Models\ReportCategory;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ReportExportService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Type')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('objet')
                    ->label('Objet')
                    ->searchable()
                    ->limit(50),

                TextColumn::make('lieu')
                    ->label('Lieu')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('date_start')
                    ->label('Debut')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('date_end')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('organizer.name')
                    ->label('Organisateur')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('missionCourrier.reference')
                    ->label('Courrier mission')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('tdrDocument.reference_doc')
                    ->label('TDR')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),

                BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Brouillon',
                        'validated' => 'Valide',
                        'archived' => 'Archive',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'draft',
                        'success' => 'validated',
                        'warning' => 'archived',
                    ]),

                BadgeColumn::make('approval_status')
                    ->label('Validation')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'not_required' => 'Non requise',
                        'pending' => 'En attente',
                        'approved' => 'Approuve',
                        'rejected' => 'Rejete',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'not_required',
                        'info' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('report_category_id')
                    ->label('Type de rapport')
                    ->options(fn () => ReportCategory::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable(),

                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'validated' => 'Valide',
                        'archived' => 'Archive',
                    ]),
            ])
            ->actions([
                Action::make('soumettre_approbation')
                    ->label('Soumettre')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(function ($record): bool {
                        $user = Auth::user();

                        if (! $user instanceof User) {
                            return false;
                        }

                        return $user->can('reports.approval.submit')
                            && $record->requires_approval
                            && in_array($record->approval_status, ['not_required', 'rejected'], true);
                    })
                    ->action(function ($record): void {
                        $firstLevel = $record->approvals()->min('level');

                        if (! $firstLevel) {
                            Notification::make()
                                ->title('Aucun approbateur configure')
                                ->danger()
                                ->send();

                            return;
                        }

                        $before = $record->only(['approval_status', 'current_approval_level', 'status']);

                        $record->update([
                            'approval_status' => 'pending',
                            'current_approval_level' => $firstLevel,
                            'submitted_at' => now(),
                            'approved_at' => null,
                            'rejected_at' => null,
                        ]);

                        $record->approvals()->update([
                            'status' => 'pending',
                            'comment' => null,
                            'decided_at' => null,
                        ]);

                        $record->notifyCurrentApprovers();

                        app(AuditLogger::class)->log(
                            action: 'reports.approval.submit',
                            entity: $record,
                            before: $before,
                            after: $record->fresh()->only(['approval_status', 'current_approval_level', 'status'])
                        );

                        Notification::make()
                            ->title('Rapport soumis a approbation')
                            ->success()
                            ->send();
                    }),

                Action::make('approuver')
                    ->label('Approuver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(function ($record): bool {
                        $user = Auth::user();

                        if (! $user instanceof User || ! $record->current_approval_level) {
                            return false;
                        }

                        if (! $user->can('reports.approval.approve') || $record->approval_status !== 'pending') {
                            return false;
                        }

                        return $record->approvals()
                            ->where('level', $record->current_approval_level)
                            ->where('approver_id', $user->id)
                            ->where('status', 'pending')
                            ->exists();
                    })
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('comment')
                            ->label('Commentaire')
                            ->placeholder('Optionnel')
                            ->nullable(),
                    ])
                    ->action(function ($record, array $data): void {
                        $approval = $record->approvals()
                            ->where('level', $record->current_approval_level)
                            ->where('approver_id', Auth::id())
                            ->where('status', 'pending')
                            ->first();

                        if (! $approval instanceof ReportApproval) {
                            Notification::make()->title('Action non autorisee')->danger()->send();

                            return;
                        }

                        $before = $record->only(['approval_status', 'current_approval_level', 'status']);

                        $approval->update([
                            'status' => 'approved',
                            'comment' => $data['comment'] ?? null,
                            'decided_at' => now(),
                        ]);

                        $pendingCurrentLevel = $record->approvals()
                            ->where('level', $record->current_approval_level)
                            ->where('status', 'pending')
                            ->exists();

                        if (! $pendingCurrentLevel) {
                            $nextLevel = $record->approvals()
                                ->where('level', '>', $record->current_approval_level)
                                ->min('level');

                            if ($nextLevel) {
                                $record->update(['current_approval_level' => $nextLevel]);
                                $record->notifyCurrentApprovers();
                            } else {
                                $record->update([
                                    'approval_status' => 'approved',
                                    'current_approval_level' => null,
                                    'approved_at' => now(),
                                    'status' => 'validated',
                                ]);

                                $record->notifyInitiatorDecision('approved');
                            }
                        }

                        app(AuditLogger::class)->log(
                            action: 'reports.approval.approve',
                            entity: $record,
                            before: $before,
                            after: $record->fresh()->only(['approval_status', 'current_approval_level', 'status']),
                            meta: [
                                'approved_level' => $approval->level,
                                'approver_id' => Auth::id(),
                                'comment' => $data['comment'] ?? null,
                            ],
                        );

                        Notification::make()
                            ->title('Approbation enregistree')
                            ->success()
                            ->send();
                    }),

                Action::make('rejeter')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(function ($record): bool {
                        $user = Auth::user();

                        if (! $user instanceof User || ! $record->current_approval_level) {
                            return false;
                        }

                        if (! $user->can('reports.approval.reject') || $record->approval_status !== 'pending') {
                            return false;
                        }

                        return $record->approvals()
                            ->where('level', $record->current_approval_level)
                            ->where('approver_id', $user->id)
                            ->where('status', 'pending')
                            ->exists();
                    })
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('comment')
                            ->label('Motif du rejet')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data): void {
                        $approval = $record->approvals()
                            ->where('level', $record->current_approval_level)
                            ->where('approver_id', Auth::id())
                            ->where('status', 'pending')
                            ->first();

                        if (! $approval instanceof ReportApproval) {
                            Notification::make()->title('Action non autorisee')->danger()->send();

                            return;
                        }

                        $before = $record->only(['approval_status', 'current_approval_level', 'status']);

                        $approval->update([
                            'status' => 'rejected',
                            'comment' => $data['comment'],
                            'decided_at' => now(),
                        ]);

                        $record->update([
                            'approval_status' => 'rejected',
                            'current_approval_level' => null,
                            'rejected_at' => now(),
                            'status' => 'draft',
                        ]);

                        $record->notifyInitiatorDecision('rejected', $data['comment']);

                        app(AuditLogger::class)->log(
                            action: 'reports.approval.reject',
                            entity: $record,
                            before: $before,
                            after: $record->fresh()->only(['approval_status', 'current_approval_level', 'status']),
                            meta: [
                                'rejected_level' => $approval->level,
                                'approver_id' => Auth::id(),
                                'comment' => $data['comment'],
                            ],
                        );

                        Notification::make()
                            ->title('Rapport rejete')
                            ->success()
                            ->send();
                    }),

                Action::make('export_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->visible(function (): bool {
                        $user = Auth::user();

                        return $user instanceof User && $user->can('reports.export');
                    })
                    ->action(function ($record) {
                        app(AuditLogger::class)->log(
                            action: 'reports.export.pdf',
                            entity: $record,
                            meta: ['report_id' => $record->id],
                        );

                        return app(ReportExportService::class)->exportPdf($record->loadMissing([
                            'category',
                            'template',
                            'organizer',
                            'missionCourrier',
                            'tdrDocument',
                        ]));
                    }),

                Action::make('export_word')
                    ->label('Word')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->visible(function (): bool {
                        $user = Auth::user();

                        return $user instanceof User && $user->can('reports.export');
                    })
                    ->action(function ($record) {
                        app(AuditLogger::class)->log(
                            action: 'reports.export.word',
                            entity: $record,
                            meta: ['report_id' => $record->id],
                        );

                        return app(ReportExportService::class)->exportWord($record->loadMissing([
                            'category',
                            'template',
                            'organizer',
                            'missionCourrier',
                            'tdrDocument',
                        ]));
                    }),

                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
