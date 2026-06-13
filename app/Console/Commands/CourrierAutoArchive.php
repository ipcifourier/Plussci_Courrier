<?php

namespace App\Console\Commands;

use App\Models\Courrier;
use App\Services\AuditLogger;
use App\Services\GedSettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CourrierAutoArchive extends Command
{
    protected $signature = 'courrier:auto-archive
                            {--days= : Override the configured courrier_archive_after_days}
                            {--dry-run : List candidates without archiving}';

    protected $description = 'Archive courriers whose statut is "Traité" and older than the configured threshold.';

    public function handle(AuditLogger $audit): int
    {
        $lifecycle = app(GedSettingsService::class)->lifecycle();
        $days = (int) ($this->option('days') ?: ($lifecycle['courrier_archive_after_days'] ?? 90));
        $dryRun = (bool) $this->option('dry-run');

        $cutoff = Carbon::now()->subDays($days);

        $query = Courrier::query()
            ->where('statut', 'Traité')
            ->where('updated_at', '<', $cutoff);

        $count = $query->count();

        if ($count === 0) {
            $this->info('Aucun courrier à archiver.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->line("<comment>[DRY-RUN]</comment> {$count} courrier(s) seraient archivés (seuil : {$days} jours).");

            $query->select(['id', 'reference', 'objet', 'updated_at'])
                ->each(function (Courrier $courrier): void {
                    $this->line("  • [{$courrier->id}] {$courrier->reference} — {$courrier->objet} (modifié le {$courrier->updated_at->format('d/m/Y')})");
                });

            return self::SUCCESS;
        }

        $archived = 0;

        $query->each(function (Courrier $courrier) use ($audit, &$archived): void {
            $before = ['statut' => $courrier->statut];

            $courrier->update(['statut' => 'Archivé']);

            $audit->log(
                action: 'courrier.auto_archived',
                entity: $courrier,
                before: $before,
                after: ['statut' => 'Archivé'],
            );

            $archived++;
        });

        $this->info("{$archived} courrier(s) archivé(s) avec succès.");

        return self::SUCCESS;
    }
}
