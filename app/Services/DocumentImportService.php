<?php

namespace App\Services;

use App\Jobs\ExtractDocumentTextJob;
use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class DocumentImportService
{
    /**
     * Import a single file and create (or append to) a Document + DocumentVersion.
     *
     * @param  string|UploadedFile $file       Absolute path or UploadedFile instance
     * @param  array  $meta {
     *     titre?:         string,
     *     type_document?: string,
     *     dossier_id?:    int,
     *     courrier_id?:   int,
     *     auteur_id?:     int,
     *     source?:        string  (upload|email|scan_folder)
     *     source_meta?:   string
     * }
     * @param  Document|null  $existingDocument  Append a new version to this document
     * @return Document
     */
    public function import(
        string|UploadedFile $file,
        array $meta = [],
        ?Document $existingDocument = null
    ): Document {
        $isUploadedFile = $file instanceof UploadedFile;
        $originalName   = $isUploadedFile ? $file->getClientOriginalName() : basename($file);
        $mimeType       = $isUploadedFile ? $file->getMimeType() : mime_content_type($file);

        return DB::transaction(function () use ($file, $meta, $existingDocument, $originalName, $mimeType, $isUploadedFile): Document {
            $auteurId = $meta['auteur_id'] ?? Auth::id() ?? 1;

            // ── 1. Create or reuse Document ──────────────────────────────────
            if ($existingDocument) {
                $document = $existingDocument;
            } else {
                $titre = $meta['titre'] ?? pathinfo($originalName, PATHINFO_FILENAME);
                $titre = empty(trim($titre)) ? $originalName : $titre;

                $document = Document::create([
                    'reference_doc'          => $this->generateReference(),
                    'titre'                  => $titre,
                    'type_document'          => $meta['type_document'] ?? 'Document',
                    'dossier_id'             => $meta['dossier_id'] ?? null,
                    'courrier_id'            => $meta['courrier_id'] ?? null,
                    'auteur_id'              => $auteurId,
                    'etat_cycle_vie'         => 'Brouillon',
                    'confidentiality_level'  => $meta['confidentiality_level'] ?? 'Standard',
                ]);
            }

            // ── 2. Determine version number ───────────────────────────────────
            $lastVersion = DocumentVersion::where('document_id', $document->id)
                ->orderByDesc('id')
                ->value('numero_version');

            $nextVersion = $lastVersion
                ? $this->incrementVersion($lastVersion)
                : '1.0';

            // ── 3. Create DocumentVersion ─────────────────────────────────────
            $version = DocumentVersion::create([
                'document_id'      => $document->id,
                'numero_version'   => $nextVersion,
                'commentaire_version' => $meta['commentaire_version'] ?? null,
                'created_by'       => $auteurId,
                'ocr_status'       => 'pending',
                'source'           => $meta['source'] ?? 'upload',
                'source_meta'      => $meta['source_meta'] ?? null,
            ]);

            // ── 4. Attach file via MediaLibrary ───────────────────────────────
            if ($isUploadedFile) {
                $media = $document
                    ->addMedia($file)
                    ->usingName($document->titre)
                    ->usingFileName($this->sanitizeFilename($originalName))
                    ->toMediaCollection('documents');
            } else {
                $media = $document
                    ->addMedia($file)
                    ->usingName($document->titre)
                    ->usingFileName($this->sanitizeFilename($originalName))
                    ->preservingOriginal()
                    ->toMediaCollection('documents');
            }

            // ── 5. Link media to version + compute checksum ───────────────────
            $checksum = hash_file('sha256', $media->getPath());
            $version->update([
                'media_id'         => $media->id,
                'checksum_sha256'  => $checksum,
            ]);

            // ── 6. Set as current version on Document ─────────────────────────
            $document->update(['version_courante_id' => $version->id]);

            // ── 7. Dispatch async OCR job ─────────────────────────────────────
            ExtractDocumentTextJob::dispatch($version->id);

            return $document->fresh();
        });
    }

    /**
     * Import multiple files at once, returning an array of created Documents.
     */
    public function importMany(array $files, array $meta = []): array
    {
        $documents = [];

        foreach ($files as $file) {
            try {
                $documents[] = $this->import($file, $meta);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $documents;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function generateReference(): string
    {
        do {
            $ref = 'DOC-' . strtoupper(Str::random(3)) . '-' . now()->format('ymd') . '-' . mt_rand(100, 999);
        } while (Document::where('reference_doc', $ref)->exists());

        return $ref;
    }

    private function incrementVersion(string $version): string
    {
        if (preg_match('/^(\d+)\.(\d+)$/', $version, $m)) {
            return $m[1] . '.' . ((int) $m[2] + 1);
        }

        return ((int) $version + 1) . '.0';
    }

    private function sanitizeFilename(string $name): string
    {
        $ext  = pathinfo($name, PATHINFO_EXTENSION);
        $base = pathinfo($name, PATHINFO_FILENAME);
        $base = Str::slug($base) ?: 'document';

        return $base . ($ext ? '.' . $ext : '');
    }
}
