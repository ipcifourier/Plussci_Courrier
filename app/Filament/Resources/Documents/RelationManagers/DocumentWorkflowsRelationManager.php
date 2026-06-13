<?php

namespace App\Filament\Resources\Documents\RelationManagers;

use App\Models\User;
use App\Models\WorkflowTemplate;
use App\Services\DocumentWorkflowService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class DocumentWorkflowsRelationManager extends RelationManager
{
    protected static string $relationship = 'workflows';

    protected static ?string $title = 'Circuits de validation';

    // ── Form (unused — all creation via custom action) ────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('template_name')
            ->defaultSort('started_at', 'desc')
            ->heading('Circuits de validation')
            ->description('Workflows d\'approbation attachés à ce document.')
            ->emptyStateHeading('Aucun circuit')
            ->emptyStateDescription('Démarrez un circuit de validation en choisissant un modèle.')
            ->emptyStateIcon('heroicon-o-arrow-path')

            ->columns([
                BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn ($record): string => $record->statusLabel())
                    ->color(fn ($record): string => match ($record->status) {
                        'pending'   => 'warning',
                        'approved'  => 'success',
                        'rejected'  => 'danger',
                        'cancelled' => 'gray',
                        default     => 'gray',
                    }),

                TextColumn::make('template_name')
                    ->label('Modèle')
                    ->searchable(),

                TextColumn::make('initiatedBy.name')
                    ->label('Lancé par')
                    ->placeholder('—'),

                TextColumn::make('progress')
                    ->label('Progression')
                    ->state(fn ($record): string => $record->progressPercent() . ' %'),

                TextColumn::make('sla_source')
                    ->label('Source SLA')
                    ->state(function ($record): string {
                        $step = $record->currentStep();

                        return $step?->slaSourceLabel() ?? '—';
                    })
                    ->badge()
                    ->color(function ($record): string {
                        $source = (string) ($record->currentStep()?->sla_source ?? '');

                        return str_starts_with($source, 'global_') ? 'primary' : 'gray';
                    }),

                TextColumn::make('started_at')
                    ->label('Démarré le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('completed_at')
                    ->label('Terminé le')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('En cours')
                    ->sortable(),
            ])

            ->filters([
                TernaryFilter::make('sla_global_rules')
                    ->label('SLA global')
                    ->placeholder('Tous')
                    ->trueLabel('Règle globale')
                    ->falseLabel('Template/manuel')
                    ->queries(
                        true: fn ($query) => $query->whereHas('steps', fn ($stepQuery) => $stepQuery
                            ->whereColumn('document_workflow_steps.step_order', 'document_workflows.current_step_order')
                            ->where('document_workflow_steps.sla_source', 'like', 'global_%')
                        ),
                        false: fn ($query) => $query->whereHas('steps', fn ($stepQuery) => $stepQuery
                            ->whereColumn('document_workflow_steps.step_order', 'document_workflows.current_step_order')
                            ->where('document_workflow_steps.sla_source', 'not like', 'global_%')
                        ),
                    ),
            ])

            ->headerActions([
                Action::make('start_workflow')
                    ->label('Démarrer un circuit')
                    ->icon('heroicon-m-arrow-path')
                    ->color('primary')
                    ->modalHeading('Démarrer un circuit de validation')
                    ->modalDescription('Sélectionnez un modèle de circuit à appliquer à ce document.')
                    ->form([
                        Forms\Components\Select::make('workflow_template_id')
                            ->label('Modèle de circuit')
                            ->options(
                                WorkflowTemplate::where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                            )
                            ->required()
                            ->native(false)
                            ->searchable(),
                    ])
                    ->action(function (array $data): void {
                        $document = $this->getOwnerRecord();
                        $template = WorkflowTemplate::findOrFail($data['workflow_template_id']);
                        $user     = Auth::user();

                        try {
                            app(DocumentWorkflowService::class)->startWorkflow($document, $template, $user);

                            Notification::make()
                                ->title('Circuit démarré')
                                ->body('Le circuit « ' . $template->name . ' » a été lancé. Le premier approbateur a été notifié.')
                                ->success()
                                ->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()
                                ->title('Impossible de démarrer le circuit')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (): bool => ! $this->getOwnerRecord()->activeWorkflow()),
            ])

            ->actions([
                // View steps in a modal
                Action::make('view_steps')
                    ->label('Étapes')
                    ->icon('heroicon-o-list-bullet')
                    ->color('gray')
                    ->modalHeading(fn ($record): string => 'Étapes — ' . $record->template_name)
                    ->modalContent(fn ($record) => view('filament.modals.workflow-steps', ['workflow' => $record->load('steps.approver')]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer'),

                Action::make('cancel_workflow')
                    ->label('Annuler')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Annuler ce circuit ?')
                    ->modalDescription('Les étapes restantes seront ignorées. Cette action est irréversible.')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Raison (optionnel)')
                            ->rows(2)
                            ->nullable(),
                    ])
                    ->action(function (array $data, $record): void {
                        $user = Auth::user();

                        try {
                            app(DocumentWorkflowService::class)->cancel($record, $user, $data['reason'] ?? null);

                            Notification::make()
                                ->title('Circuit annulé')
                                ->success()
                                ->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()
                                ->title('Erreur')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn ($record): bool =>
                        (function () use ($record): bool {
                            /** @var User|null $user */
                            $user = Auth::user();

                            return $record->isPending() && (
                                Auth::id() === $record->initiated_by ||
                                $user?->hasRole('Super Admin')
                            );
                        })()
                    ),
            ])

            ->striped();
    }
}
