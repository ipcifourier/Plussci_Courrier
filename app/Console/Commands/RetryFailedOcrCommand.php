<?php

namespace App\Console\Commands;

use App\Jobs\ExtractDocumentTextJob;
use App\Models\DocumentVersion;
use Illuminate\Console\Command;

class RetryFailedOcrCommand extends Command
{
    protected $signature = 'acquisition:retry-failed-ocr
                            {--hours=24   : Relancer les versions en échec créées dans les N dernières heures}
                            {--limit=50   : Nombre maximum de versions à relancer par exécution}
                            {--dry-run    : Afficher les versions candidates sans les relancer}';

    protected $description = 'Relance le job OCR sur les versions en échec avec un délai de back-off';

    public function handle(): int
    {
        $hours  = max(1, (int) $this->option('hours'));
        $limit  = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');

        $versions = DocumentVersion::query()
            ->where('ocr_status', 'failed')
            ->where('created_at', '>=', now()->subHours($hours))
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'document_id', 'created_at']);

        if ($versions->isEmpty()) {
            $this->line("Aucune version OCR en échec (< {$hours} h).");

            return self::SUCCESS;
        }

        $this->line("🔁 {$versions->count()} version(s) candidate(s).");

        if ($dryRun) {
            $this->warn('[DRY-RUN] Aucun job ne sera dispatché.');
            foreach ($versions as $v) {
                $this->line("  • Version #{$v->id} (document #{$v->document_id}, créée le {$v->created_at})");
            }

            return self::SUCCESS;
        }

        // Stagger dispatches to avoid a thundering herd on the queue.
        $delaySeconds = 0;

        foreach ($versions as $version) {
            $version->update(['ocr_status' => 'pending']);

            ExtractDocumentTextJob::dispatch($version->id)
                ->delay(now()->addSeconds($delaySeconds));

            $this->line("  → Version #{$version->id} replanifiée (délai : {$delaySeconds} s).");

            $delaySeconds += 5; // stagger by 5 s each
        }

        $this->info("✓ {$versions->count()} version(s) replanifiée(s).");

        return self::SUCCESS;
    }
}
