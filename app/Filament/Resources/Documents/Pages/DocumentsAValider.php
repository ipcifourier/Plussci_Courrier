<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DocumentsAValider extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return false;
    }

    public function getTitle(): string
    {
        return 'Documents a valider';
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
                            ->where('approver_id', Auth::id())
                            ->where('status', 'pending')
                            ->whereColumn('document_workflow_steps.step_order', 'document_workflows.current_step_order');
                    });
            });
    }
}
