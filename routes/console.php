<?php

use App\Console\Commands\AuditPurgeCommand;
use App\Console\Commands\CleanStalePresenceSessions;
use App\Console\Commands\CourrierAutoArchive;
use App\Console\Commands\GedArchiveStale;
use App\Console\Commands\ImportEmailDocumentsCommand;
use App\Console\Commands\ProcessScanFolderCommand;
use App\Console\Commands\SendAgendaRemindersCommand;
use App\Console\Commands\SendAuditWeeklyReportCommand;
use App\Console\Commands\SendCourrierDeadlineAlertsCommand;
use App\Console\Commands\SendDeadlineAlerts;
use App\Console\Commands\EscalateOverdueWorkflowSteps;
use App\Jobs\DetectAuditAnomaliesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Lifecycle auto-archiving — runs daily at 02:00

Schedule::command(GedArchiveStale::class)->dailyAt('02:00')->withoutOverlapping();
Schedule::command(CourrierAutoArchive::class)->dailyAt('02:30')->withoutOverlapping();

// Acquisition — email import every 15 minutes (toggle via GED settings or IMAP_HOST config)

Schedule::command(ImportEmailDocumentsCommand::class)
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->skip(fn () => ! app(\App\Services\GedSettingsService::class)->imapScheduleEnabled());

// Acquisition — scan folder every 5 minutes

Schedule::command(ProcessScanFolderCommand::class)
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Suivi & traçabilité — alertes d'échéance quotidiennes à 07:00

Schedule::command(SendDeadlineAlerts::class)->dailyAt('07:00')->withoutOverlapping();

// Agenda — rappels de rendez-vous et réunions à venir (J-1 et H-2)

Schedule::command(SendAgendaRemindersCommand::class)->hourly()->withoutOverlapping();

// Courriers — alertes délai de réponse approchant (matin 07:30)

Schedule::command(SendCourrierDeadlineAlertsCommand::class)->dailyAt('07:30')->withoutOverlapping();

// Co-édition — purge des sessions de présence fantômes toutes les 5 minutes

Schedule::command(CleanStalePresenceSessions::class)->everyFiveMinutes()->withoutOverlapping();

// Workflow — escalade des étapes en retard toutes les 30 minutes

Schedule::command(EscalateOverdueWorkflowSteps::class)->everyThirtyMinutes()->withoutOverlapping();

// Audit — détection d'anomalies toutes les heures

Schedule::job(DetectAuditAnomaliesJob::class)->hourly()->withoutOverlapping();

// Audit — rapport hebdomadaire chaque lundi à 08:00

Schedule::command(SendAuditWeeklyReportCommand::class)->weeklyOn(1, '08:00')->withoutOverlapping();

// Audit — purge mensuelle des logs anciens (1er du mois à 03:00)

Schedule::command(AuditPurgeCommand::class, ['--keep-months=12'])->monthlyOn(1, '03:00')->withoutOverlapping();
