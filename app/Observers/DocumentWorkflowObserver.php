<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Document;
use App\Services\DocumentWorkflowService;
use Illuminate\Support\Facades\Auth;

class DocumentWorkflowObserver
{
    public function created(Document $document): void
    {
        $this->attemptAutoStart($document, 'created');
    }

    public function updated(Document $document): void
    {
        if (! $document->wasChanged(['confidentiality_level', 'type_document'])) {
            return;
        }

        $this->attemptAutoStart($document, 'updated');
    }

    private function attemptAutoStart(Document $document, string $source): void
    {
        try {
            $workflow = app(DocumentWorkflowService::class)->startAutoWorkflowIfEligible(
                $document,
                $document->auteur,
            );

            if (! $workflow) {
                return;
            }

            AuditLog::create([
                'actor_id' => Auth::id() ?? $document->auteur_id,
                'action' => 'documents.workflow.auto_start',
                'entity_type' => Document::class,
                'entity_id' => $document->id,
                'meta_json' => [
                    'workflow_id' => $workflow->id,
                    'template_id' => $workflow->workflow_template_id,
                    'source' => $source,
                ],
                'created_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
