<?php

namespace App\Services;

use App\Models\Document;
use App\Models\AppSetting;
use Illuminate\Support\Str;

class CourrierSlaSettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = AppSetting::query()->where('key', $key)->first();

        return $setting?->value ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        AppSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * @return array{
     *   task_reminder_days_before: array<int>,
     *   imputation_reminder_days_before: array<int>,
     *   task_escalation_after_overdue_days: int,
     *   imputation_escalation_after_overdue_days: int,
     *   enable_task_escalation: bool,
     *   enable_imputation_escalation: bool,
     *   send_overdue_daily: bool,
     *   workflow_default_sla_hours: int,
     *   workflow_sla_by_type: array<string,int>,
     *   workflow_sla_by_priority: array<string,int>,
     *   workflow_sla_by_confidentiality: array<string,int>
     * }
     */
    public function config(): array
    {
        $default = [
            'task_reminder_days_before' => (array) config('courriers.sla.task_reminder_days_before', [3, 1, 0]),
            'imputation_reminder_days_before' => (array) config('courriers.sla.imputation_reminder_days_before', [3, 1, 0]),
            'task_escalation_after_overdue_days' => (int) config('courriers.sla.task_escalation_after_overdue_days', 2),
            'imputation_escalation_after_overdue_days' => (int) config('courriers.sla.imputation_escalation_after_overdue_days', 1),
            'enable_task_escalation' => (bool) config('courriers.sla.enable_task_escalation', true),
            'enable_imputation_escalation' => (bool) config('courriers.sla.enable_imputation_escalation', true),
            'send_overdue_daily' => (bool) config('courriers.sla.send_overdue_daily', true),
            'workflow_default_sla_hours' => 24,
            'workflow_sla_by_type' => [
                'contrat' => 48,
                'facture' => 24,
            ],
            'workflow_sla_by_priority' => [
                'urgente' => 8,
                'haute' => 16,
            ],
            'workflow_sla_by_confidentiality' => [
                'confidentiel' => 12,
                'personnel' => 24,
            ],
        ];

        $stored = $this->get('courriers.sla', $default);
        $stored = is_array($stored) ? $stored : [];

        return [
            'task_reminder_days_before' => $this->normalizeDays($stored['task_reminder_days_before'] ?? $default['task_reminder_days_before']),
            'imputation_reminder_days_before' => $this->normalizeDays($stored['imputation_reminder_days_before'] ?? $default['imputation_reminder_days_before']),
            'task_escalation_after_overdue_days' => max(1, (int) ($stored['task_escalation_after_overdue_days'] ?? $default['task_escalation_after_overdue_days'])),
            'imputation_escalation_after_overdue_days' => max(1, (int) ($stored['imputation_escalation_after_overdue_days'] ?? $default['imputation_escalation_after_overdue_days'])),
            'enable_task_escalation' => (bool) ($stored['enable_task_escalation'] ?? $default['enable_task_escalation']),
            'enable_imputation_escalation' => (bool) ($stored['enable_imputation_escalation'] ?? $default['enable_imputation_escalation']),
            'send_overdue_daily' => (bool) ($stored['send_overdue_daily'] ?? $default['send_overdue_daily']),
            'workflow_default_sla_hours' => max(1, (int) ($stored['workflow_default_sla_hours'] ?? $default['workflow_default_sla_hours'])),
            'workflow_sla_by_type' => $this->normalizeHoursMap($stored['workflow_sla_by_type'] ?? $default['workflow_sla_by_type']),
            'workflow_sla_by_priority' => $this->normalizeHoursMap($stored['workflow_sla_by_priority'] ?? $default['workflow_sla_by_priority']),
            'workflow_sla_by_confidentiality' => $this->normalizeHoursMap($stored['workflow_sla_by_confidentiality'] ?? $default['workflow_sla_by_confidentiality']),
        ];
    }

    /**
     * Resolve effective SLA and source for a workflow step.
     *
     * Rule:
     * - If step SLA is custom (!= 24), keep it (template_custom).
     * - If step SLA is default (24), apply global override by priority/confidentiality/type.
     *
     * @return array{hours:int, source:string}
     */
    public function resolveWorkflowStepSla(Document $document, int $stepSlaHours): array
    {
        $stepSlaHours = max(1, $stepSlaHours);

        if ($stepSlaHours !== 24) {
            return [
                'hours' => $stepSlaHours,
                'source' => 'template_custom',
            ];
        }

        $config = $this->config();
        $defaultHours = max(1, (int) ($config['workflow_default_sla_hours'] ?? 24));

        $document->loadMissing('courrier');

        $typeKey = Str::lower(trim((string) ($document->type_document ?? '')));
        $priorityKey = Str::lower(trim((string) ($document->courrier?->priorite ?? data_get($document->metadata_json, 'priorite', ''))));
        $confidentialityKey = Str::lower(trim((string) ($document->confidentiality_level ?? '')));

        $byPriority = (array) ($config['workflow_sla_by_priority'] ?? []);
        if ($priorityKey !== '' && array_key_exists($priorityKey, $byPriority)) {
            return [
                'hours' => max(1, (int) $byPriority[$priorityKey]),
                'source' => 'global_priority',
            ];
        }

        $byConfidentiality = (array) ($config['workflow_sla_by_confidentiality'] ?? []);
        if ($confidentialityKey !== '' && array_key_exists($confidentialityKey, $byConfidentiality)) {
            return [
                'hours' => max(1, (int) $byConfidentiality[$confidentialityKey]),
                'source' => 'global_confidentiality',
            ];
        }

        $byType = (array) ($config['workflow_sla_by_type'] ?? []);
        if ($typeKey !== '' && array_key_exists($typeKey, $byType)) {
            return [
                'hours' => max(1, (int) $byType[$typeKey]),
                'source' => 'global_type',
            ];
        }

        return [
            'hours' => $defaultHours,
            'source' => 'global_default',
        ];
    }

    /**
     * @return array<int>
     */
    public function parseDaysCsv(string $csv): array
    {
        $parts = preg_split('/\s*,\s*/', trim($csv), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return $this->normalizeDays(array_map('intval', $parts));
    }

    /**
     * @param array<int|string|mixed> $days
     * @return array<int>
     */
    private function normalizeDays(array $days): array
    {
        $normalized = [];

        foreach ($days as $day) {
            $value = (int) $day;

            if ($value < 0) {
                continue;
            }

            $normalized[] = $value;
        }

        if ($normalized === []) {
            $normalized = [0];
        }

        $normalized = array_values(array_unique($normalized));
        rsort($normalized);

        return $normalized;
    }

    /**
     * @param array<mixed> $map
     * @return array<string,int>
     */
    public function normalizeHoursMap(array $map): array
    {
        $normalized = [];

        foreach ($map as $key => $value) {
            $k = Str::lower(trim((string) $key));
            $hours = (int) $value;

            if ($k === '' || $hours < 1) {
                continue;
            }

            $normalized[$k] = $hours;
        }

        return $normalized;
    }
}
