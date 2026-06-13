<?php

namespace App\Console\Commands;

use App\Models\DocumentWorkflowStep;
use App\Notifications\WorkflowStepEscalatedNotification;
use App\Services\AuditLogger;
use Illuminate\Console\Command;

class EscalateOverdueWorkflowSteps extends Command
{
    protected $signature = 'workflow:escalate-overdue
                            {--dry-run : Affiche les escalades sans envoyer de notifications}';

    protected $description = 'Escalade les étapes workflow en retard et notifie les acteurs concernés.';

    public function handle(AuditLogger $audit): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $steps = DocumentWorkflowStep::query()
            ->where('status', 'pending')
            ->whereNull('escalated_at')
            ->whereHas('workflow', fn ($query) => $query
                ->where('status', 'pending')
                ->whereColumn('current_step_order', 'document_workflow_steps.step_order'))
            ->with(['workflow.document', 'approver', 'escalationUser'])
            ->orderBy('created_at')
            ->get();

        if ($steps->isEmpty()) {
            $this->line('Aucune étape à escalader.');

            return self::SUCCESS;
        }

        $escalatedCount = 0;

        foreach ($steps as $step) {
            $deadline = $step->deadlineAt();

            if (! $deadline || $deadline->isFuture()) {
                continue;
            }

            $workflow = $step->workflow;
            $document = $workflow?->document;

            if (! $workflow || ! $document) {
                continue;
            }

            if ($dryRun) {
                $this->line(sprintf(
                    '[DRY-RUN] Escalade step #%d (workflow #%d, doc #%d) échéance %s',
                    $step->id,
                    $workflow->id,
                    $document->id,
                    $deadline->format('d/m/Y H:i')
                ));
                $escalatedCount++;

                continue;
            }

            if ($step->approver) {
                $step->approver->notify(new WorkflowStepEscalatedNotification($document, $workflow, $step, false));
            }

            if ($step->escalationUser && $step->escalation_user_id !== $step->approver_id) {
                $step->escalationUser->notify(new WorkflowStepEscalatedNotification($document, $workflow, $step, true));
            }

            $step->updateQuietly([
                'due_at' => $step->due_at ?? $deadline,
                'escalated_at' => now(),
            ]);

            $audit->log(
                action: 'documents.workflow.step_escalated',
                entity: $document,
                meta: [
                    'workflow_id' => $workflow->id,
                    'step_id' => $step->id,
                    'approver_id' => $step->approver_id,
                    'escalation_user_id' => $step->escalation_user_id,
                    'due_at' => $deadline->toIso8601String(),
                ],
            );

            $escalatedCount++;
        }

        if ($dryRun) {
            $this->line("[DRY-RUN] {$escalatedCount} étape(s) seraient escaladées.");
        } else {
            $this->info("{$escalatedCount} étape(s) escaladées.");
        }

        return self::SUCCESS;
    }
}
