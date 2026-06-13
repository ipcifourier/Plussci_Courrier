<?php

namespace App\Console\Commands;

use App\Services\DocumentImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class WatchScanFolderCommand extends Command
{
    protected $signature = 'acquisition:watch-scan-folder
                            {--dossier=  : ID du dossier GED cible (optionnel)}
                            {--folder=   : Chemin du dossier de scan (écrase ACQUISITION_SCAN_FOLDER)}
                            {--interval= : Intervalle de poll en secondes (défaut : 10)}';

    protected $description = 'Surveille le dossier scanner en continu et importe chaque nouveau fichier dès sa détection';

    /** Extensions prises en charge (même liste que ProcessScanFolderCommand). */
    private const EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'tiff', 'tif', 'bmp', 'doc', 'docx', 'txt'];

    public function handle(DocumentImportService $importer): int
    {
        $scanFolder = (string) ($this->option('folder') ?: config('acquisition.scan_folder'));
        $doneFolder = (string) config('acquisition.scan_done_folder');
        $interval   = max(1, (int) ($this->option('interval') ?: 10));

        if ($scanFolder === '' || $doneFolder === '') {
            $this->error('Chemins non configurés (ACQUISITION_SCAN_FOLDER / ACQUISITION_SCAN_DONE_FOLDER).');

            return self::FAILURE;
        }

        foreach ([$scanFolder, $doneFolder] as $dir) {
            if (! is_dir($dir)) {
                File::makeDirectory($dir, 0755, true);
                $this->line("📁 Dossier créé : {$dir}");
            }
        }

        $dossierId = $this->option('dossier') ? (int) $this->option('dossier') : null;

        $this->info("👁  Surveillance active : {$scanFolder}");
        $this->line("   Intervalle : {$interval} s — CTRL+C pour arrêter.");

        // Track already-seen files to avoid re-importing a file
        // that was just moved but not yet deleted (race condition).
        $processed = [];

        while (true) {
            $files = array_filter(
                glob($scanFolder . DIRECTORY_SEPARATOR . '*') ?: [],
                fn (string $f) => is_file($f)
                    && in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), self::EXTENSIONS, true)
                    && ! isset($processed[$f])
            );

            foreach ($files as $filePath) {
                $baseName = basename($filePath);

                // Wait for write completion: skip if the file is still growing.
                $size1 = filesize($filePath);
                sleep(1);
                clearstatcache(true, $filePath);
                $size2 = @filesize($filePath);

                if ($size1 !== $size2) {
                    $this->line('[' . now()->format('H:i:s') . "] ⏳ En cours d'écriture, on attend : {$baseName}");
                    continue;
                }

                try {
                    $doc = $importer->import($filePath, [
                        'titre'         => pathinfo($baseName, PATHINFO_FILENAME),
                        'type_document' => 'Document',
                        'dossier_id'    => $dossierId,
                        'source'        => 'scan_folder',
                        'source_meta'   => $filePath,
                    ]);

                    $dest = $doneFolder . DIRECTORY_SEPARATOR . date('Ymd_His_') . $baseName;
                    File::move($filePath, $dest);

                    $this->info('[' . now()->format('H:i:s') . "] ✔ [{$doc->reference_doc}] {$baseName}");
                } catch (\Throwable $e) {
                    $this->error('[' . now()->format('H:i:s') . "] ✖ {$baseName} : " . $e->getMessage());
                    // Mark as processed so we don't loop indefinitely on a broken file.
                    $processed[$filePath] = true;
                }
            }

            sleep($interval);
        }
    }
}
