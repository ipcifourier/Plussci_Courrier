<?php

namespace App\Console\Commands;

use App\Services\DocumentPresenceService;
use Illuminate\Console\Command;

class CleanStalePresenceSessions extends Command
{
    protected $signature = 'presence:clean
                            {--dry-run : Count stale sessions without deleting them}';

    protected $description = 'Remove stale document presence sessions (users who left without a clean disconnect).';

    public function handle(DocumentPresenceService $presenceService): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $count = \App\Models\DocumentSession::where(
                'last_seen_at',
                '<',
                now()->subMinutes(DocumentPresenceService::TTL_MINUTES)
            )->count();

            $this->line("[DRY-RUN] {$count} session(s) expirée(s) seraient supprimées (TTL : " . DocumentPresenceService::TTL_MINUTES . ' min).');

            return self::SUCCESS;
        }

        $deleted = $presenceService->cleanStaleSessions();

        $this->info("Nettoyage terminé : {$deleted} session(s) expirée(s) supprimée(s).");

        return self::SUCCESS;
    }
}
