<?php

namespace App\Console\Commands;

use App\Models\Imputation;
use App\Models\Task;
use App\Notifications\ImputationRelanceNotification;
use App\Notifications\TaskDeadlineReminderNotification;
use App\Services\AuditLogger;
use App\Services\CourrierSlaSettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class SendDeadlineAlerts extends Command
{
    protected $signature = 'deadline:send-alerts
                            {--dry-run : Affiche les alertes sans les envoyer}
                            {--hours=24 : Fenetre d\'anticipation legacy (heures), convertie en jours SLA}';

    protected $description = 'Envoie des alertes pour les tâches et imputations proches de leur échéance ou en retard.';

    public function handle(AuditLogger $audit, CourrierSlaSettingsService $sla): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $hours  = (int) ($this->option('hours') ?: 24);
        $config = $sla->config();

        // Backward compatibility for manual calls using --hours
        $hoursAsDays = max(0, (int) ceil($hours / 24));
        $config['task_reminder_days_before'] = array_values(array_unique(array_merge(
            $config['task_reminder_days_before'],
            [$hoursAsDays, 0],
        )));

        $taskCount       = $this->processTasks($audit, $dryRun, $config);
        $imputationCount = $this->processImputations($audit, $dryRun, $config);

        if ($dryRun) {
            $this->line("<comment>[DRY-RUN]</comment> {$taskCount} tâche(s) et {$imputationCount} imputation(s) recevraient une alerte.");
        } else {
            $this->info("{$taskCount} alerte(s) tâche et {$imputationCount} relance(s) imputation envoyées.");
        }

        return self::SUCCESS;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function processTasks(AuditLogger $audit, bool $dryRun, array $config): int
    {
        $reminderDays = array_map('intval', (array) ($config['task_reminder_days_before'] ?? [3, 1, 0]));
        $maxReminderDays = max(0, ...$reminderDays);
        $sendOverdueDaily = (bool) ($config['send_overdue_daily'] ?? true);
        $enableEscalation = (bool) ($config['enable_task_escalation'] ?? true);
        $escalationAfterDays = max(1, (int) ($config['task_escalation_after_overdue_days'] ?? 2));

        $horizon = Carbon::today()->addDays($maxReminderDays);
        $today   = Carbon::today();

        $tasks = Task::query()
            ->whereNotNull('due_date')
            ->whereNotIn('status', ['done', 'cancelled'])
            ->where('due_date', '<=', $horizon)
            ->whereNotNull('assignee_id')
            ->with(['assignee', 'assigner'])
            ->get();

        if ($tasks->isEmpty()) {
            $this->line('Aucune tâche à alerter.');

            return 0;
        }

        $count = 0;

        foreach ($tasks as $task) {
            $daysUntilDue = (int) $today->diffInDays($task->due_date, false);
            $isOverdue = $daysUntilDue < 0;
            $daysOverdue = $isOverdue ? abs($daysUntilDue) : 0;
            $alertedToday = $task->alerte_envoyee_at?->isToday() ?? false;

            $inReminderWindow = in_array($daysUntilDue, $reminderDays, true);
            $shouldSendPrimary = ! $alertedToday && ($inReminderWindow || ($isOverdue && $sendOverdueDaily));

            $shouldEscalate = $enableEscalation
                && $isOverdue
                && $daysOverdue === $escalationAfterDays
                && $task->assigner_id !== null
                && $task->assigner_id !== $task->assignee_id
                && $task->assigner !== null;

            if (! $shouldSendPrimary && ! $shouldEscalate) {
                continue;
            }

            $label = $task->isOverdue() ? '[EN RETARD]' : '[BIENTÔT]';
            $due   = $task->due_date->format('d/m/Y');

            if ($dryRun) {
                if ($shouldSendPrimary) {
                    $this->line("  Tâche {$label} #{$task->id} « {$task->titre} » (échéance : {$due}) → {$task->assignee->name}");
                    $count++;
                }

                if ($shouldEscalate) {
                    $this->line("  [ESCALADE] Tâche #{$task->id} « {$task->titre} » en retard de {$daysOverdue} jour(s) → {$task->assigner->name}");
                    $count++;
                }

                continue;
            }

            if ($shouldSendPrimary) {
                Notification::send($task->assignee, new TaskDeadlineReminderNotification($task));
                $task->updateQuietly(['alerte_envoyee_at' => now()]);

                $audit->log(
                    action: 'task.deadline_alert_sent',
                    entity: $task,
                    after: ['alerte_envoyee_at' => now()->toIso8601String()],
                    meta: [
                        'days_until_due' => $daysUntilDue,
                    ],
                );

                $count++;
            }

            if ($shouldEscalate && $task->assigner) {
                Notification::send($task->assigner, new TaskDeadlineReminderNotification($task));

                $audit->log(
                    action: 'task.deadline_escalation_sent',
                    entity: $task,
                    meta: [
                        'days_overdue' => $daysOverdue,
                        'escalation_to' => $task->assigner_id,
                    ],
                );

                $count++;
            }
        }

        return $count;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function processImputations(AuditLogger $audit, bool $dryRun, array $config): int
    {
        $reminderDays = array_map('intval', (array) ($config['imputation_reminder_days_before'] ?? [3, 1, 0]));
        $maxReminderDays = max(0, ...$reminderDays);
        $sendOverdueDaily = (bool) ($config['send_overdue_daily'] ?? true);
        $enableEscalation = (bool) ($config['enable_imputation_escalation'] ?? true);
        $escalationAfterDays = max(1, (int) ($config['imputation_escalation_after_overdue_days'] ?? 1));

        $today = Carbon::today();
        $horizon = Carbon::today()->addDays($maxReminderDays);

        $imputations = Imputation::query()
            ->whereNotNull('delai_traitement')
            ->where('statut_traitement', '!=', 'Traité')
            ->where('delai_traitement', '<=', $horizon)
            ->whereNotNull('destinataire_id')
            ->with(['destinataire', 'expediteur', 'courrier'])
            ->get();

        if ($imputations->isEmpty()) {
            $this->line('Aucune imputation à relancer.');

            return 0;
        }

        $count = 0;

        foreach ($imputations as $imputation) {
            $daysUntilDue = (int) $today->diffInDays($imputation->delai_traitement, false);
            $isOverdue = $daysUntilDue < 0;
            $daysOverdue = $isOverdue ? abs($daysUntilDue) : 0;
            $alertedToday = $imputation->relance_envoyee_at?->isToday() ?? false;

            $inReminderWindow = in_array($daysUntilDue, $reminderDays, true);
            $shouldSendPrimary = ! $alertedToday && ($inReminderWindow || ($isOverdue && $sendOverdueDaily));

            $shouldEscalate = $enableEscalation
                && $isOverdue
                && $daysOverdue === $escalationAfterDays
                && $imputation->expediteur_id !== null
                && $imputation->expediteur_id !== $imputation->destinataire_id
                && $imputation->expediteur !== null;

            if (! $shouldSendPrimary && ! $shouldEscalate) {
                continue;
            }

            $ref = $imputation->courrier?->reference ?? '—';
            $due = $imputation->delai_traitement->format('d/m/Y');

            if ($dryRun) {
                if ($shouldSendPrimary) {
                    $this->line("  Imputation #{$imputation->id} courrier « {$ref} » (délai : {$due}) → {$imputation->destinataire->name}");
                    $count++;
                }

                if ($shouldEscalate) {
                    $this->line("  [ESCALADE] Imputation #{$imputation->id} courrier « {$ref} » en retard de {$daysOverdue} jour(s) → {$imputation->expediteur->name}");
                    $count++;
                }

                continue;
            }

            if ($shouldSendPrimary) {
                Notification::send($imputation->destinataire, new ImputationRelanceNotification($imputation));
                $imputation->updateQuietly(['relance_envoyee_at' => now()]);

                $audit->log(
                    action: 'imputation.relance_sent',
                    entity: $imputation,
                    after: ['relance_envoyee_at' => now()->toIso8601String()],
                    meta: [
                        'days_until_due' => $daysUntilDue,
                    ],
                );

                $count++;
            }

            if ($shouldEscalate && $imputation->expediteur) {
                Notification::send($imputation->expediteur, new ImputationRelanceNotification($imputation));

                $audit->log(
                    action: 'imputation.relance_escalation_sent',
                    entity: $imputation,
                    meta: [
                        'days_overdue' => $daysOverdue,
                        'escalation_to' => $imputation->expediteur_id,
                    ],
                );

                $count++;
            }
        }

        return $count;
    }
}
