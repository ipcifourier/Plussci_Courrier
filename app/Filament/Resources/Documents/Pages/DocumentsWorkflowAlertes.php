<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class DocumentsWorkflowAlertes extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return false;
    }

    public function getTitle(): string
    {
        return 'Alertes workflow (SLA)';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('retour_documents')
                ->label('Tous les documents')
                ->icon('heroicon-o-arrow-left')
                ->url(DocumentResource::getUrl('index')),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->whereHas('workflows', function (Builder $workflowQuery): void {
                $workflowQuery
                    ->where('status', 'pending')
                    ->whereHas('steps', function (Builder $stepQuery): void {
                        $stepQuery
                            ->where('status', 'pending')
                            ->whereColumn('document_workflow_steps.step_order', 'document_workflows.current_step_order')
                            ->where(function (Builder $alertQuery): void {
                                $alertQuery
                                    ->whereNotNull('document_workflow_steps.escalated_at')
                                    ->orWhere(function (Builder $overdueQuery): void {
                                        $overdueQuery
                                            ->whereNotNull('document_workflow_steps.due_at')
                                            ->where('document_workflow_steps.due_at', '<', now());
                                    });
                            });
                    });
            });
    }
}
