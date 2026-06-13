<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\User;
use App\Services\ArchiveService;
use App\Services\AuditLogger;
use App\Services\GedSettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GedArchiveStale extends Command
{
    protected $signature = 'ged:archive-stale
                            {--days= : Override the configured archive_after_days}
                            {--dry-run : List candidates without actually archiving}';

    protected $description = 'Archive GED documents whose état_cycle_vie is "Valide" and older than the configured threshold.';

    public function handle(AuditLogger $audit, ArchiveService $archiveService): int
    {
        $lifecycle = app(GedSettingsService::class)->lifecycle();
        $days = (int) ($this->option('days') ?: ($lifecycle['document_archive_after_days'] ?? 365));
        $dryRun = (bool) $this->option('dry-run');

        $cutoff = Carbon::now()->subDays($days);

        $query = Document::query()
            ->where('etat_cycle_vie', 'Valide')
            ->where('updated_at', '<', $cutoff);

        $count = $query->count();

        if ($count === 0) {
            $this->info('Aucun document à archiver.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->line("<comment>[DRY-RUN]</comment> {$count} document(s) seraient archivés (seuil : {$days} jours).");

            $query->select(['id', 'reference_doc', 'titre', 'updated_at'])
                ->each(function (Document $doc): void {
                    $this->line("  • [{$doc->id}] {$doc->reference_doc} — {$doc->titre} (modifié le {$doc->updated_at->format('d/m/Y')})");
                });

            return self::SUCCESS;
        }

        $archived = 0;

        // Use system user (id=1) for automated archiving; gracefully degrade if absent.
        $systemUser = User::find(1) ?? User::first();

        $query->each(function (Document $doc) use ($audit, $archiveService, $systemUser, &$archived): void {
            $before = ['etat_cycle_vie' => $doc->etat_cycle_vie];

            $archiveService->archiveDocument(
                document:   $doc,
                user:       $systemUser,
                reason:     'Archivage automatique (inactivité)',
                legalBasis: '',
            );

            $audit->log(
                action: 'ged.document.auto_archived',
                entity: $doc,
                before: $before,
                after: ['etat_cycle_vie' => 'Archive'],
            );

            $archived++;
        });

        $this->info("{$archived} document(s) archivé(s) avec succès.");

        return self::SUCCESS;
    }
}
