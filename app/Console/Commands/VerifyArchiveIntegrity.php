<?php

namespace App\Console\Commands;

use App\Models\ArchiveRecord;
use App\Models\User;
use App\Services\ArchiveService;
use App\Services\AuditLogger;
use Illuminate\Console\Command;

class VerifyArchiveIntegrity extends Command
{
    protected $signature = 'ged:verify-archive-integrity
                            {--id= : Verify a single archive record by ID}
                            {--status=pending : Filter by integrity_status (pending|all)}
                            {--dry-run : Report without updating records}';

    protected $description = 'Verify SHA-256 integrity of all archived documents\' files.';

    public function handle(ArchiveService $archiveService, AuditLogger $audit): int
    {
        $query = ArchiveRecord::query()->with(['document', 'document.media']);

        if ($this->option('id')) {
            $query->where('id', (int) $this->option('id'));
        } elseif ($this->option('status') !== 'all') {
            $query->where('integrity_status', $this->option('status'));
        }

        $records = $query->get();

        if ($records->isEmpty()) {
            $this->info('Aucun enregistrement d\'archive à vérifier.');
            return self::SUCCESS;
        }

        $dryRun      = (bool) $this->option('dry-run');
        $systemUser  = User::find(1) ?? User::first();
        $ok          = 0;
        $corrupted   = 0;

        // Table header
        $this->table(
            ['ID', 'Document', 'Statut', 'Action'],
            $records->map(function (ArchiveRecord $rec) use (
                $archiveService, $audit, $dryRun, $systemUser, &$ok, &$corrupted
            ): array {
                $doc = $rec->document;

                if ($dryRun) {
                    $current = $archiveService->computeChecksum($doc);
                    $status  = $current === $rec->integrity_checksum ? 'verified' : 'corrupted';
                    $action  = '[DRY-RUN]';
                } else {
                    $status = $archiveService->verifyIntegrity($rec, $systemUser);
                    $action = 'Mis à jour';

                    $audit->log(
                        action: 'ged.archive.integrity_checked',
                        entity: $doc,
                        after: ['integrity_status' => $status],
                    );
                }

                if ($status === 'verified') {
                    $ok++;
                } else {
                    $corrupted++;
                }

                return [
                    $rec->id,
                    $doc?->reference_doc ?? '—',
                    $status,
                    $action,
                ];
            })->all()
        );

        $this->info(sprintf(
            'Résultat : %d intègre(s), %d corrompu(s).',
            $ok,
            $corrupted,
        ));

        return $corrupted === 0 ? self::SUCCESS : self::FAILURE;
    }
}
