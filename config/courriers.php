<?php

return [
    'email_notifications' => env('COURRIERS_EMAIL_NOTIFICATIONS', false),

    /*
    |--------------------------------------------------------------------------
    | Lifecycle / Archivage automatique
    |--------------------------------------------------------------------------
    | Nombre de jours après lesquels :
    | - Un courrier à statut "Traité" passe en "Archivé"
    | - Un document à état "Valide" passe en "Archive"
    */
    'lifecycle' => [
        'courrier_archive_after_days' => (int) env('COURRIER_ARCHIVE_AFTER_DAYS', 90),
        'document_archive_after_days' => (int) env('DOCUMENT_ARCHIVE_AFTER_DAYS', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | Quota stockage par utilisateur (en mégaoctets)
    |--------------------------------------------------------------------------
    */
    'upload_quota_mb' => (int) env('UPLOAD_QUOTA_MB', 500),

    /*
    |--------------------------------------------------------------------------
    | SLA alerting and escalation
    |--------------------------------------------------------------------------
    */
    'sla' => [
        'task_reminder_days_before' => array_values(array_filter(
            array_map('intval', explode(',', (string) env('COURRIERS_TASK_REMINDER_DAYS_BEFORE', '3,1,0'))),
            fn (int $value): bool => $value >= 0,
        )),
        'imputation_reminder_days_before' => array_values(array_filter(
            array_map('intval', explode(',', (string) env('COURRIERS_IMPUTATION_REMINDER_DAYS_BEFORE', '3,1,0'))),
            fn (int $value): bool => $value >= 0,
        )),
        'task_escalation_after_overdue_days' => (int) env('COURRIERS_TASK_ESCALATION_AFTER_OVERDUE_DAYS', 2),
        'imputation_escalation_after_overdue_days' => (int) env('COURRIERS_IMPUTATION_ESCALATION_AFTER_OVERDUE_DAYS', 1),
        'enable_task_escalation' => (bool) env('COURRIERS_ENABLE_TASK_ESCALATION', true),
        'enable_imputation_escalation' => (bool) env('COURRIERS_ENABLE_IMPUTATION_ESCALATION', true),
        'send_overdue_daily' => (bool) env('COURRIERS_SEND_OVERDUE_DAILY', true),
    ],
];
