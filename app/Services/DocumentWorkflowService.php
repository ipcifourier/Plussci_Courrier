<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentWorkflow;
use App\Models\DocumentWorkflowStep;
use App\Models\User;
use App\Models\WorkflowTemplate;
use App\Notifications\WorkflowCompletedNotification;
use App\Notifications\WorkflowStepRequestedNotification;
use App\Services\CourrierSlaSettingsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DocumentWorkflowService
{
    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Start a new workflow instance from a template on a document.
     * Creates a DocumentWorkflow + one DocumentWorkflowStep per template step.
     * Notifies the first approver.
     *
     * Throws if the document already has an active (pending) workflow.
     */
    public function startWorkflow(
        Document         $document,
        WorkflowTemplate $template,
        User             $initiatedBy,
    ): DocumentWorkflow {
        if ($this->getActiveWorkflow($document)) {
            throw new \RuntimeException('Ce document a déjà un circuit de validation en cours.');
        }

        $steps = $template->steps()->with('approver')->get();

        if ($steps->isEmpty()) {
            throw new \RuntimeException('Ce modèle de circuit ne contient aucune étape.');
        }

        $workflow = DocumentWorkflow::create([
            'document_id'          => $document->id,
            'workflow_template_id' => $template->id,
            'template_name'        => $template->name,
            'initiated_by'         => $initiatedBy->id,
            'status'               => 'pending',
            'current_step_order'   => 1,
            'started_at'           => now(),
        ]);

        foreach ($steps as $templateStep) {
            $templateSlaHours = max(1, (int) ($templateStep->sla_hours ?? 24));
            $slaResolution = app(CourrierSlaSettingsService::class)->resolveWorkflowStepSla($document, $templateSlaHours);
            $slaHours = (int) ($slaResolution['hours'] ?? $templateSlaHours);
            $slaSource = (string) ($slaResolution['source'] ?? 'template_default');
            $isFirstStep = (int) $templateStep->step_order === 1;

            DocumentWorkflowStep::create([
                'document_workflow_id' => $workflow->id,
                'step_order'           => $templateStep->step_order,
                'label'                => $templateStep->label,
                'action'               => $templateStep->action,
                'sla_hours'            => $slaHours,
                'sla_source'           => $slaSource,
                'approver_id'          => $templateStep->approver_user_id,
                'escalation_user_id'   => $templateStep->escalation_user_id,
                'status'               => $templateStep->step_order === 1 ? 'pending' : 'pending',
                'due_at'               => $isFirstStep ? now()->addHours($slaHours) : null,
            ]);
        }

        // Notify first approver
        $firstStep = $workflow->steps()->where('step_order', 1)->first();
        if ($firstStep && $firstStep->approver) {
            $firstStep->approver->notify(new WorkflowStepRequestedNotification($document, $workflow, $firstStep));
        }

        return $workflow->fresh(['steps']);
    }

    /**
     * Auto-start a workflow when a matching template exists.
     * Returns the started workflow, or null if no template applies.
     */
    public function startAutoWorkflowIfEligible(Document $document, ?User $initiatedBy = null): ?DocumentWorkflow
    {
        if ($this->getActiveWorkflow($document)) {
            return null;
        }

        $template = $this->resolveAutomaticTemplateForDocument($document);

        if (! $template) {
            return null;
        }

        $initiator = $initiatedBy
            ?? $document->auteur
            ?? Auth::user()
            ?? User::query()->orderBy('id')->first();

        if (! $initiator) {
            return null;
        }

        return $this->startWorkflow($document, $template, $initiator);
    }

    /**
     * Approve the current pending step.
     * Advances to the next step, or closes the workflow as "approved" if last step.
     */
    public function approve(
        DocumentWorkflowStep $step,
        User                 $approver,
        ?string              $comment = null,
    ): void {
        $this->ensureCanDecide($step, $approver);

        $step->update([
            'status'     => 'approved',
            'comment'    => $comment,
            'decided_at' => now(),
        ]);

        $workflow = $step->workflow;

        // Find next step in the workflow
        $nextStep = $workflow->steps()
            ->where('step_order', '>', $step->step_order)
            ->orderBy('step_order')
            ->first();

        if ($nextStep) {
            $nextDueAt = now()->addHours(max(1, (int) ($nextStep->sla_hours ?? 24)));

            $workflow->update(['current_step_order' => $nextStep->step_order]);
            $nextStep->updateQuietly([
                'due_at' => $nextDueAt,
                'escalated_at' => null,
            ]);

            $nextStep->approver?->notify(
                new WorkflowStepRequestedNotification($workflow->document, $workflow, $nextStep)
            );
        } else {
            $this->closeWorkflow($workflow, 'approved');
        }
    }

    /**
     * Reject the current pending step — closes the entire workflow.
     */
    public function reject(
        DocumentWorkflowStep $step,
        User                 $approver,
        string               $comment,
    ): void {
        $this->ensureCanDecide($step, $approver);

        $step->update([
            'status'     => 'rejected',
            'comment'    => $comment,
            'decided_at' => now(),
        ]);

        $this->closeWorkflow($step->workflow, 'rejected', $comment);
    }

    /**
     * Cancel a workflow (only by initiator or super admin).
     */
    public function cancel(DocumentWorkflow $workflow, User $cancelledBy, ?string $reason = null): void
    {
        if ($workflow->isCompleted()) {
            throw new \RuntimeException('Ce circuit est déjà terminé.');
        }

        // Use a direct model query to avoid orderBy in bulk UPDATE
        DocumentWorkflowStep::where('document_workflow_id', $workflow->id)
            ->where('status', 'pending')
            ->update(['status' => 'skipped']);

        $this->closeWorkflow($workflow, 'cancelled', $reason);
    }

    /**
     * Return the active (pending) workflow for a document, or null.
     */
    public function getActiveWorkflow(Document $document): ?DocumentWorkflow
    {
        return $document->workflows()
            ->where('status', 'pending')
            ->with(['steps.approver', 'initiatedBy'])
            ->latest()
            ->first();
    }

    /**
     * Return all workflow steps assigned to a user that are still pending.
     */
    public function getPendingStepsForUser(User $user): Collection
    {
        return DocumentWorkflowStep::where('approver_id', $user->id)
            ->where('status', 'pending')
            ->with(['workflow.document', 'workflow.initiatedBy'])
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Return all pending steps across all documents, scoped by step order matching the workflow's current step.
     * Only returns steps at the current active position in their workflow.
     */
    public function getActivePendingStepsForUser(User $user): Collection
    {
        return DocumentWorkflowStep::where('approver_id', $user->id)
            ->where('status', 'pending')
            ->whereHas('workflow', fn ($q) => $q
                ->where('status', 'pending')
                ->whereColumn('current_step_order', 'document_workflow_steps.step_order')
            )
            ->with(['workflow.document', 'workflow.initiatedBy'])
            ->orderBy('created_at')
            ->get();
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function ensureCanDecide(DocumentWorkflowStep $step, User $approver): void
    {
        if ($step->approver_id !== $approver->id && ! $approver->hasRole('Super Admin')) {
            throw new \RuntimeException('Vous n\'êtes pas l\'approbateur désigné de cette étape.');
        }

        if (! $step->isPending()) {
            throw new \RuntimeException('Cette étape a déjà été traitée.');
        }

        if ($step->workflow->status !== 'pending') {
            throw new \RuntimeException('Ce circuit n\'est plus actif.');
        }

        if ($step->step_order !== $step->workflow->current_step_order) {
            throw new \RuntimeException('Cette étape n\'est pas l\'étape courante du circuit.');
        }
    }

    private function closeWorkflow(DocumentWorkflow $workflow, string $status, ?string $comment = null): void
    {
        $workflow->update([
            'status'        => $status,
            'final_comment' => $comment,
            'completed_at'  => now(),
        ]);

        // Notify initiator
        $workflow->loadMissing(['document', 'initiatedBy']);
        $document = $workflow->document;

        if ($workflow->initiatedBy) {
            $workflow->initiatedBy->notify(new WorkflowCompletedNotification($document, $workflow));
        }

        // Also notify document author if different from initiator
        $document->loadMissing('auteur');
        if ($document->auteur && $document->auteur_id !== $workflow->initiated_by) {
            $document->auteur->notify(new WorkflowCompletedNotification($document, $workflow));
        }
    }

    private function resolveAutomaticTemplateForDocument(Document $document): ?WorkflowTemplate
    {
        $automaticTemplates = WorkflowTemplate::query()
            ->where('is_active', true)
            ->where('auto_start', true)
            ->with('steps.approver.roles')
            ->orderBy('id')
            ->get();

        if ($automaticTemplates->isEmpty()) {
            return null;
        }

        $matchingTemplates = $automaticTemplates
            ->filter(fn (WorkflowTemplate $template): bool => $template->supportsDocument($document))
            ->values();

        if ($matchingTemplates->isEmpty()) {
            return null;
        }

        // V1 rule: confidential documents must route through an N2 approver.
        if ($this->isConfidential($document)) {
            return $matchingTemplates
                ->first(fn (WorkflowTemplate $template): bool => $this->templateHasN2Approver($template));
        }

        return $matchingTemplates->first();
    }

    private function templateHasN2Approver(WorkflowTemplate $template): bool
    {
        $template->loadMissing('steps.approver.roles');

        foreach ($template->steps as $step) {
            if ($step->approver?->hasRole('Approbateur N2')) {
                return true;
            }
        }

        return false;
    }

    private function isConfidential(Document $document): bool
    {
        return (string) $document->confidentiality_level === 'Confidentiel';
    }
}
