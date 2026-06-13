<?php

namespace App\Services;

use App\Models\ArchiveRecord;
use App\Models\Document;
use App\Models\User;
use Carbon\Carbon;

/**
 * Electronic archive service.
 *
 * Responsibilities:
 *  - Transition a Document to the "Archive" lifecycle state
 *  - Capture a SHA-256 integrity fingerprint of all attached files
 *  - Record a frozen metadata snapshot (manifest_json)
 *  - Verify integrity on demand (compares live files against stored checksum)
 *  - Provide default retention durations per document type
 *  - Export the archive registry as structured JSON
 */
class ArchiveService
{
    public function retentionYearsForType(string $type): int
    {
        $retentionByType = app(GedSettingsService::class)->retentionByType();

        return (int) ($retentionByType[$type] ?? 5);
    }

    // ── Archive document ──────────────────────────────────────────────────────

    /**
     * Archive a document:
     *  1. Set etat_cycle_vie = Archive (if not already)
     *  2. Compute SHA-256 fingerprint of all attached files
     *  3. Create/update an ArchiveRecord with the metadata snapshot
     *
     * Returns the created/updated ArchiveRecord.
     */
    public function archiveDocument(
        Document $document,
        User $user,
        string $reason = '',
        string $legalBasis = '',
        ?int $retentionYears = null,
    ): ArchiveRecord {
        // Transition state
        if ($document->etat_cycle_vie !== 'Archive') {
            $document->update(['etat_cycle_vie' => 'Archive']);
        }

        $retentionYears ??= $this->retentionYearsForType($document->type_document);
        $archivedAt       = Carbon::now();
        $expiresAt        = $archivedAt->copy()->addYears($retentionYears)->toDateString();
        $checksum         = $this->computeChecksum($document);
        $snapshot         = $this->buildSnapshot($document);

        return ArchiveRecord::updateOrCreate(
            ['document_id' => $document->id],
            [
                'archived_by'          => $user->id,
                'archived_at'          => $archivedAt,
                'reason'               => $reason ?: null,
                'legal_basis'          => $legalBasis ?: null,
                'retention_years'      => $retentionYears,
                'retention_expires_at' => $expiresAt,
                'integrity_checksum'   => $checksum,
                'integrity_status'     => 'pending',
                'verified_at'          => null,
                'verified_by'          => null,
                'manifest_json'        => $snapshot,
            ]
        );
    }

    // ── Integrity verification ────────────────────────────────────────────────

    /**
     * Recompute SHA-256 on current media and compare with the stored checksum.
     * Updates integrity_status, verified_at, verified_by on the record.
     *
     * Returns 'verified' or 'corrupted'.
     */
    public function verifyIntegrity(ArchiveRecord $record, User $verifier): string
    {
        $record->load('document');
        $currentChecksum = $this->computeChecksum($record->document);

        $status = ($currentChecksum === $record->integrity_checksum)
            ? 'verified'
            : 'corrupted';

        $record->update([
            'integrity_status' => $status,
            'verified_at'      => Carbon::now(),
            'verified_by'      => $verifier->id,
        ]);

        return $status;
    }

    // ── Manifest export ───────────────────────────────────────────────────────

    /**
     * Build the JSON export structure for the archive registry.
     * Suitable for legal auditors or long-term preservation systems.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<ArchiveRecord> $records
     * @return array
     */
    public function generateManifestJson($records): array
    {
        return [
            'generated_at' => Carbon::now()->toIso8601String(),
            'generator'    => config('app.name') . ' — Registre d\'archivage électronique',
            'total'        => $records->count(),
            'records'      => $records->map(function (ArchiveRecord $rec): array {
                return [
                    'archive_id'            => $rec->id,
                    'document_id'           => $rec->document_id,
                    'reference_doc'         => $rec->document?->reference_doc,
                    'titre'                 => $rec->document?->titre,
                    'type_document'         => $rec->document?->type_document,
                    'confidentiality_level' => $rec->document?->confidentiality_level,
                    'archived_at'           => $rec->archived_at?->toIso8601String(),
                    'archived_by'           => $rec->archivedBy?->name,
                    'reason'                => $rec->reason,
                    'legal_basis'           => $rec->legal_basis,
                    'retention_years'       => $rec->retention_years,
                    'retention_expires_at'  => $rec->retention_expires_at?->toDateString(),
                    'integrity_checksum'    => $rec->integrity_checksum,
                    'integrity_status'      => $rec->integrity_status,
                    'verified_at'           => $rec->verified_at?->toIso8601String(),
                    'verified_by'           => $rec->verifiedBy?->name,
                    'metadata_snapshot'     => $rec->manifest_json,
                ];
            })->values()->all(),
        ];
    }

    // ── Internal helpers ──────────────────────────────────────────────────────

    /**
     * Compute a SHA-256 fingerprint over all media files of a document.
     * If the document has no media, returns a hash of its primary key + titre.
     */
    public function computeChecksum(Document $document): string
    {
        $media = $document->getMedia('documents');

        if ($media->isEmpty()) {
            return hash('sha256', $document->id . '|' . $document->titre . '|' . $document->reference_doc);
        }

        $hashes = $media->map(function ($item): string {
            $path = $item->getPath();

            return file_exists($path) ? hash_file('sha256', $path) : hash('sha256', $item->uuid);
        });

        return hash('sha256', $hashes->implode('|'));
    }

    /**
     * Build a frozen metadata snapshot of a document.
     */
    private function buildSnapshot(Document $document): array
    {
        return [
            'id'                    => $document->id,
            'reference_doc'         => $document->reference_doc,
            'titre'                 => $document->titre,
            'type_document'         => $document->type_document,
            'etat_cycle_vie'        => 'Archive',
            'confidentiality_level' => $document->confidentiality_level,
            'auteur_id'             => $document->auteur_id,
            'dossier_id'            => $document->dossier_id,
            'courrier_id'           => $document->courrier_id,
            'tags_json'             => $document->tags_json,
            'keywords'              => $document->keywords,
            'metadata_json'         => $document->metadata_json,
            'created_at'            => $document->created_at?->toIso8601String(),
            'updated_at'            => $document->updated_at?->toIso8601String(),
            'media_files'           => $document->getMedia('documents')->map(fn ($m) => [
                'uuid'      => $m->uuid,
                'file_name' => $m->file_name,
                'mime_type' => $m->mime_type,
                'size'      => $m->size,
            ])->values()->all(),
        ];
    }
}
