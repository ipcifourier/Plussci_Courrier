<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Writer as CsvWriter;

/**
 * AU4 — Purge les anciens enregistrements d'audit.
 * Archive d'abord en CSV dans storage/app/audit-archives/, puis supprime les lignes.
 * Planifié le 1er de chaque mois à 03:00.
 */
class AuditPurgeCommand extends Command
{
    protected $signature = 'audit:purge
                            {--keep-months=12 : Nombre de mois à conserver (défaut 12)}
                            {--dry-run : Liste les entrées sans purger}
                            {--no-archive : Supprime sans archiver en CSV}';

    protected $description = 'Archive puis supprime les entrées d\'audit plus anciennes que N mois.';

    public function handle(AuditLogger $audit): int
    {
        $keepMonths = max(1, (int) ($this->option('keep-months') ?: 12));
        $dryRun     = (bool) $this->option('dry-run');
        $noArchive  = (bool) $this->option('no-archive');

        $cutoff = Carbon::now()->subMonths($keepMonths)->endOfDay();

        $count = AuditLog::query()->where('created_at', '<=', $cutoff)->count();

        if ($count === 0) {
            $this->info("Aucune entrée à purger (seuil : {$keepMonths} mois, avant le {$cutoff->format('d/m/Y')}).");
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->line("<comment>[DRY-RUN]</comment> {$count} entrée(s) seraient purgées (avant le {$cutoff->format('d/m/Y')}).");
            return self::SUCCESS;
        }

        if (! $noArchive) {
            $archiveFile = 'audit-archives/audit-purge-' . now()->format('Ymd-His') . '.csv';
            Storage::disk('local')->makeDirectory('audit-archives');

            $writer = new CsvWriter();
            $writer->openToFile(storage_path('app/' . $archiveFile));

            $writer->addRow(Row::fromValues(['id', 'actor_id', 'action', 'entity_type', 'entity_id', 'ip_address', 'before_json', 'after_json', 'created_at']));

            AuditLog::query()
                ->where('created_at', '<=', $cutoff)
                ->orderBy('id')
                ->chunkById(500, function ($logs) use ($writer): void {
                    foreach ($logs as $log) {
                        $writer->addRow(Row::fromValues([
                            $log->id,
                            $log->actor_id,
                            $log->action,
                            $log->entity_type,
                            $log->entity_id,
                            $log->ip_address,
                            $log->before_json ? json_encode($log->before_json) : '',
                            $log->after_json ? json_encode($log->after_json) : '',
                            $log->created_at?->toIso8601String(),
                        ]));
                    }
                });

            $writer->close();
            $this->info("Archive CSV créée : {$archiveFile}");
        }

        AuditLog::query()->where('created_at', '<=', $cutoff)->delete();

        $audit->log('audit.purge', null, [], ['purged_count' => $count, 'cutoff' => $cutoff->toIso8601String()]);

        $this->info("✓ {$count} entrée(s) purgées (avant le {$cutoff->format('d/m/Y')}).");

        return self::SUCCESS;
    }
}
