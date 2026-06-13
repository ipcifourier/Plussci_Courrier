<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\ClientManager;

class EmailImportService
{
    public function __construct(
        private readonly DocumentImportService $importer,
    ) {}

    /**
     * Connect to an IMAP mailbox and import attachments as Documents.
     *
     * @param  array  $config  {host, port, username, password, protocol, encryption, folder, validate_cert}
     * @param  int|null  $dossierId  Target dossier for imported documents
     * @return array  {imported: Document[], skipped: int, errors: string[]}
     */
    public function importFromMailbox(array $config, ?int $dossierId = null): array
    {
        $imported = [];
        $skipped  = 0;
        $errors   = [];

        try {
            $client = $this->buildClient($config);
            $client->connect();

            $folder   = $client->getFolder($config['folder'] ?? 'INBOX');
            $messages = $folder->query()->unseen()->get();

            foreach ($messages as $message) {
                try {
                    $attachments = $message->getAttachments();

                    if ($attachments->isEmpty()) {
                        $skipped++;
                        continue;
                    }

                    $sender  = $message->getFrom()->first()?->mail ?? 'inconnu';
                    $subject = (string) $message->getSubject();

                    foreach ($attachments as $attachment) {
                        $tmpPath = tempnam(sys_get_temp_dir(), 'email_att_') . '_' . $attachment->getName();

                        file_put_contents($tmpPath, $attachment->getContent());

                        $doc = $this->importer->import($tmpPath, [
                            'titre'        => $subject ?: $attachment->getName(),
                            'type_document' => 'Document',
                            'dossier_id'   => $dossierId,
                            'source'       => 'email',
                            'source_meta'  => "De: {$sender} | Sujet: {$subject}",
                        ]);

                        $imported[] = $doc;

                        @unlink($tmpPath);
                    }

                    // Mark as read
                    $message->setFlag('Seen');
                } catch (Throwable $e) {
                    $errors[] = 'Message ' . $message->getUid() . ': ' . $e->getMessage();
                    Log::warning('EmailImportService: failed to import message', ['error' => $e->getMessage()]);
                }
            }

            $client->disconnect();
        } catch (Throwable $e) {
            $errors[] = 'Connexion IMAP: ' . $e->getMessage();
            Log::error('EmailImportService: connection failed', ['error' => $e->getMessage()]);
        }

        return compact('imported', 'skipped', 'errors');
    }

    /**
     * Test the connection and return true/false with an optional error message.
     */
    public function testConnection(array $config): array
    {
        try {
            $client = $this->buildClient($config);
            $client->connect();
            $client->disconnect();

            return ['ok' => true, 'message' => 'Connexion réussie'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    // -------------------------------------------------------------------------

    private function buildClient(array $config): Client
    {
        $manager = new ClientManager([]);

        return $manager->make([
            'host'          => $config['host'],
            'port'          => (int) ($config['port'] ?? 993),
            'encryption'    => $config['encryption'] ?? 'ssl',
            'validate_cert' => (bool) ($config['validate_cert'] ?? false),
            'username'      => $config['username'],
            'password'      => $config['password'],
            'protocol'      => $config['protocol'] ?? 'imap',
        ]);
    }
}
