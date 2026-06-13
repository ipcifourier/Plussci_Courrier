<?php

namespace App\Console\Commands;

use App\Models\Dossier;
use App\Services\EmailImportService;
use Illuminate\Console\Command;

class ImportEmailDocumentsCommand extends Command
{
    protected $signature = 'acquisition:import-emails
                            {--dossier= : ID du dossier cible (optionnel)}
                            {--host= : Serveur IMAP (écrase config)}
                            {--port=993 : Port IMAP}
                            {--username= : Adresse e-mail}
                            {--password= : Mot de passe}
                            {--folder=INBOX : Dossier IMAP à lire}
                            {--dry-run : Afficher sans importer}';

    protected $description = 'Importe les pièces jointes des e-mails non lus dans la GED';

    public function handle(EmailImportService $service): int
    {
        $config = [
            'host'          => $this->option('host') ?: config('acquisition.imap.host'),
            'port'          => (int) ($this->option('port') ?: config('acquisition.imap.port')),
            'encryption'    => config('acquisition.imap.encryption'),
            'validate_cert' => config('acquisition.imap.validate_cert'),
            'username'      => $this->option('username') ?: config('acquisition.imap.username'),
            'password'      => $this->option('password') ?: config('acquisition.imap.password'),
            'folder'        => $this->option('folder') ?: config('acquisition.imap.folder'),
            'protocol'      => config('acquisition.imap.protocol'),
        ];

        if (empty($config['host']) || empty($config['username'])) {
            $this->error('Configuration IMAP incomplète. Renseignez IMAP_HOST et IMAP_USERNAME dans .env');
            return self::FAILURE;
        }

        // Test connection first
        $this->line('🔌 Test de connexion IMAP...');
        $test = $service->testConnection($config);

        if (! $test['ok']) {
            $this->error('Connexion échouée : ' . $test['message']);
            return self::FAILURE;
        }

        $this->info('✓ Connexion OK');

        if ($this->option('dry-run')) {
            $this->warn('[DRY-RUN] Aucun document ne sera importé.');
            return self::SUCCESS;
        }

        $dossierId = $this->option('dossier')
            ? (int) $this->option('dossier')
            : null;

        if ($dossierId && ! Dossier::find($dossierId)) {
            $this->error("Dossier #{$dossierId} introuvable.");
            return self::FAILURE;
        }

        $this->line('📩 Import des e-mails en cours...');
        $result = $service->importFromMailbox($config, $dossierId);

        $this->info(sprintf('✓ %d document(s) importé(s)', count($result['imported'])));

        if ($result['skipped'] > 0) {
            $this->line("  ↳ {$result['skipped']} e-mail(s) sans pièce jointe ignoré(s).");
        }

        foreach ($result['errors'] as $err) {
            $this->warn('  ⚠ ' . $err);
        }

        foreach ($result['imported'] as $doc) {
            $this->line("  ✔ [{$doc->reference_doc}] {$doc->titre}");
        }

        return self::SUCCESS;
    }
}
