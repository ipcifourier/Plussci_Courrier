<?php

namespace App\Console\Commands;

use App\Services\DocumentImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ProcessScanFolderCommand extends Command
{
    protected $signature = 'acquisition:process-scan-folder
                            {--dossier= : ID du dossier cible (optionnel)}
                            {--folder= : Chemin du dossier de scan (écrase config)}
                            {--dry-run : Liste les fichiers sans les importer}';

    protected $description = 'Importe les fichiers déposés dans le dossier de numérisation';

    public function handle(DocumentImportService $importer): int
    {
        $scanFolder = $this->option('folder') ?: config('acquisition.scan_folder');
        $doneFolder = config('acquisition.scan_done_folder');

        if (empty($scanFolder) || empty($doneFolder)) {
            $this->error('Chemins de dossier non configurés (ACQUISITION_SCAN_FOLDER / ACQUISITION_SCAN_DONE_FOLDER).');
            return self::FAILURE;
        }

        if (! is_dir($scanFolder)) {
            File::makeDirectory($scanFolder, 0755, true);
            $this->line("📁 Dossier créé : {$scanFolder}");
        }

        if (! is_dir($doneFolder)) {
            File::makeDirectory($doneFolder, 0755, true);
        }

        $extensions = ['pdf', 'jpg', 'jpeg', 'png', 'tiff', 'tif', 'bmp', 'doc', 'docx', 'txt'];
        $pattern    = $scanFolder . DIRECTORY_SEPARATOR . '*';
        $files      = array_filter(glob($pattern), fn ($f) => is_file($f) && in_array(
            strtolower(pathinfo($f, PATHINFO_EXTENSION)),
            $extensions,
            true
        ));

        if (empty($files)) {
            $this->line("📂 Aucun fichier à traiter dans : {$scanFolder}");
            return self::SUCCESS;
        }

        $this->line(sprintf('📂 %d fichier(s) détecté(s)…', count($files)));

        if ($this->option('dry-run')) {
            $this->warn('[DRY-RUN] Liste des fichiers (sans import) :');
            foreach ($files as $file) {
                $this->line('  • ' . basename($file));
            }
            return self::SUCCESS;
        }

        $dossierId  = $this->option('dossier') ? (int) $this->option('dossier') : null;
        $imported   = 0;
        $failed     = 0;

        foreach ($files as $filePath) {
            $baseName = basename($filePath);

            try {
                $doc = $importer->import($filePath, [
                    'titre'       => pathinfo($baseName, PATHINFO_FILENAME),
                    'type_document' => 'Document',
                    'dossier_id'  => $dossierId,
                    'source'      => 'scan_folder',
                    'source_meta' => $filePath,
                ]);

                // Move to done folder
                $dest = $doneFolder . DIRECTORY_SEPARATOR . date('Ymd_His_') . $baseName;
                File::move($filePath, $dest);

                $this->info("  ✔ [{$doc->reference_doc}] {$baseName}");
                $imported++;
            } catch (\Throwable $e) {
                $this->error("  ✖ {$baseName} : " . $e->getMessage());
                $failed++;
            }
        }

        $this->line(sprintf('✓ %d importé(s), %d échec(s)', $imported, $failed));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
