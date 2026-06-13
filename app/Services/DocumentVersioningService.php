<?php

namespace App\Services;

use App\Jobs\ExtractDocumentTextJob;
use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DocumentVersioningService
{
    // ─── Public API ───────────────────────────────────────────────────────────

    /**
     * Create a new version from a temporary uploaded file.
     * Detects duplicate content via SHA-256 checksum and throws if identical.
     *
     * @throws \RuntimeException when a duplicate checksum is detected
     */
    public function createVersion(
        Document $document,
        string   $tempPath,
        string   $originalName,
        string   $mimeType,
        string   $comment  = '',
        string   $type     = 'minor', // 'minor' | 'major'
    ): DocumentVersion {
        if (! file_exists($tempPath)) {
            throw new \RuntimeException("Le fichier temporaire est introuvable : {$tempPath}");
        }

        $checksum = hash_file('sha256', $tempPath);

        // Duplicate detection
        $duplicate = $this->detectDuplicate($document, $checksum);
        if ($duplicate) {
            throw new \RuntimeException(
                "Ce fichier est identique à la version {$duplicate->numero_version} (même contenu SHA-256). Aucune nouvelle version créée."
            );
        }

        $versionNumber = $this->nextVersionNumber($document, $type);

        // Add file to Spatie MediaLibrary (moves file from temp path)
        $media = $document
            ->addMedia($tempPath)
            ->usingFileName($originalName)
            ->usingName(pathinfo($originalName, PATHINFO_FILENAME))
            ->toMediaCollection('documents');

        $version = DocumentVersion::create([
            'document_id'         => $document->id,
            'numero_version'      => $versionNumber,
            'media_id'            => $media->id,
            'checksum_sha256'     => $checksum,
            'commentaire_version' => $comment ?: null,
            'created_by'          => Auth::id(),
            'ocr_status'          => 'pending',
            'source'              => 'upload',
        ]);

        // Promote to current version
        $document->update(['version_courante_id' => $version->id]);

        // Trigger OCR + classification pipeline
        ExtractDocumentTextJob::dispatch($version->id);

        return $version;
    }

    /**
     * Scan a document's media collection and create version records for any
     * media items that do not yet have a corresponding DocumentVersion.
     * Used in afterCreate / afterSave hooks.
     */
    public function syncUnversionedMedia(Document $document): void
    {
        $versionedMediaIds = $document->versions()
            ->whereNotNull('media_id')
            ->pluck('media_id')
            ->flip(); // use as a Set (O(1) lookup)

        foreach ($document->getMedia('documents') as $media) {
            if ($versionedMediaIds->has($media->id)) {
                continue; // Already tracked
            }

            $checksum = $this->checksumForMedia($media);

            // Skip if this exact file content is already versioned
            if ($checksum && $this->detectDuplicate($document, $checksum)) {
                continue;
            }

            $versionNumber = $this->nextVersionNumber($document);

            $version = DocumentVersion::create([
                'document_id'         => $document->id,
                'numero_version'      => $versionNumber,
                'media_id'            => $media->id,
                'checksum_sha256'     => $checksum,
                'commentaire_version' => null,
                'created_by'          => Auth::id(),
                'ocr_status'          => $checksum ? 'pending' : 'unavailable',
                'source'              => 'upload',
            ]);

            // Set the first synced version as current if none is set yet
            $document->refresh();
            if (! $document->version_courante_id) {
                $document->update(['version_courante_id' => $version->id]);
            }

            // Re-add to set so next iteration's nextVersionNumber() is correct
            $versionedMediaIds->put($media->id, true);

            if ($checksum) {
                ExtractDocumentTextJob::dispatch($version->id);
            }
        }
    }

    /**
     * Change the current (displayed) version for a document.
     */
    public function setCurrentVersion(Document $document, DocumentVersion $version): void
    {
        $document->update(['version_courante_id' => $version->id]);
    }

    /**
     * Calculate the next semantic version number for a document.
     * Minor bump: 1.0 → 1.1 → 1.2 …
     * Major bump: 1.0 → 2.0
     */
    public function nextVersionNumber(Document $document, string $type = 'minor'): string
    {
        $latest = $document->versions()->orderByDesc('id')->value('numero_version');

        if (! $latest) {
            return '1.0';
        }

        $parts = explode('.', (string) $latest);
        $major = (int) ($parts[0] ?? 1);
        $minor = (int) ($parts[1] ?? 0);

        return $type === 'major'
            ? ($major + 1) . '.0'
            : $major . '.' . ($minor + 1);
    }

    /**
     * Returns the existing DocumentVersion that matches the given checksum, or null.
     */
    public function detectDuplicate(Document $document, string $checksum): ?DocumentVersion
    {
        return $document->versions()
            ->where('checksum_sha256', $checksum)
            ->first();
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function checksumForMedia(Media $media): ?string
    {
        $path = $media->getPath();
        return file_exists($path) ? hash_file('sha256', $path) : null;
    }
}
