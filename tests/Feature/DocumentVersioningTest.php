<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\DocumentVersioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentVersioningTest extends TestCase
{
    use RefreshDatabase;

    private DocumentVersioningService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DocumentVersioningService::class);
        $this->user    = User::factory()->create();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private static int $seq = 0;

    private function makeDocument(array $overrides = []): Document
    {
        self::$seq++;
        return Document::create(array_merge([
            'reference_doc'         => 'VER-' . self::$seq,
            'titre'                 => 'Document versioning ' . self::$seq,
            'type_document'         => 'Document',
            'etat_cycle_vie'        => 'Brouillon',
            'auteur_id'             => $this->user->id,
            'confidentiality_level' => 'Standard',
        ], $overrides));
    }

    private function makeVersion(Document $doc, array $overrides = []): DocumentVersion
    {
        return DocumentVersion::create(array_merge([
            'document_id'    => $doc->id,
            'numero_version' => '1.0',
            'created_by'     => $this->user->id,
            'ocr_status'     => 'pending',
            'source'         => 'upload',
        ], $overrides));
    }

    // ─── nextVersionNumber() ─────────────────────────────────────────────────

    public function test_next_version_number_is_1_0_for_new_document(): void
    {
        $doc = $this->makeDocument();

        $this->assertSame('1.0', $this->service->nextVersionNumber($doc));
    }

    public function test_next_version_number_minor_bump(): void
    {
        $doc = $this->makeDocument();
        $this->makeVersion($doc, ['numero_version' => '1.0']);

        $this->assertSame('1.1', $this->service->nextVersionNumber($doc, 'minor'));
    }

    public function test_next_version_number_bumps_from_latest(): void
    {
        $doc = $this->makeDocument();
        $this->makeVersion($doc, ['numero_version' => '1.0']);
        $this->makeVersion($doc, ['numero_version' => '1.1']);
        $this->makeVersion($doc, ['numero_version' => '1.2']);

        $this->assertSame('1.3', $this->service->nextVersionNumber($doc, 'minor'));
    }

    public function test_next_version_number_major_bump(): void
    {
        $doc = $this->makeDocument();
        $this->makeVersion($doc, ['numero_version' => '1.3']);

        $this->assertSame('2.0', $this->service->nextVersionNumber($doc, 'major'));
    }

    public function test_next_version_number_major_bump_resets_minor(): void
    {
        $doc = $this->makeDocument();
        $this->makeVersion($doc, ['numero_version' => '3.7']);

        $this->assertSame('4.0', $this->service->nextVersionNumber($doc, 'major'));
    }

    // ─── detectDuplicate() ───────────────────────────────────────────────────

    public function test_detect_duplicate_returns_version_when_checksum_matches(): void
    {
        $doc     = $this->makeDocument();
        $version = $this->makeVersion($doc, [
            'checksum_sha256' => 'abc123def456abc123def456abc123def456abc123def456abc123def456ab12',
        ]);

        $found = $this->service->detectDuplicate($doc, 'abc123def456abc123def456abc123def456abc123def456abc123def456ab12');

        $this->assertNotNull($found);
        $this->assertSame($version->id, $found->id);
    }

    public function test_detect_duplicate_returns_null_when_no_match(): void
    {
        $doc = $this->makeDocument();
        $this->makeVersion($doc, [
            'checksum_sha256' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa1111',
        ]);

        $result = $this->service->detectDuplicate($doc, 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb2222');

        $this->assertNull($result);
    }

    public function test_detect_duplicate_is_scoped_to_document(): void
    {
        $doc1 = $this->makeDocument();
        $doc2 = $this->makeDocument();

        $checksum = 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc3333';

        // Checksum exists for doc1 only
        $this->makeVersion($doc1, ['checksum_sha256' => $checksum]);

        // Should return null for doc2 even though checksum exists in the system
        $this->assertNull($this->service->detectDuplicate($doc2, $checksum));
        $this->assertNotNull($this->service->detectDuplicate($doc1, $checksum));
    }

    // ─── setCurrentVersion() ─────────────────────────────────────────────────

    public function test_set_current_version_updates_version_courante_id(): void
    {
        $doc = $this->makeDocument();
        $v1  = $this->makeVersion($doc, ['numero_version' => '1.0']);
        $v2  = $this->makeVersion($doc, ['numero_version' => '1.1']);

        // Initially set v1 as current
        $doc->update(['version_courante_id' => $v1->id]);

        $this->service->setCurrentVersion($doc, $v2);

        $doc->refresh();
        $this->assertSame($v2->id, $doc->version_courante_id);
    }

    public function test_set_current_version_can_rollback_to_older_version(): void
    {
        $doc = $this->makeDocument();
        $v1  = $this->makeVersion($doc, ['numero_version' => '1.0']);
        $v2  = $this->makeVersion($doc, ['numero_version' => '1.1']);

        $doc->update(['version_courante_id' => $v2->id]);

        // Roll back to v1
        $this->service->setCurrentVersion($doc, $v1);

        $doc->refresh();
        $this->assertSame($v1->id, $doc->version_courante_id);
    }

    // ─── syncUnversionedMedia() ──────────────────────────────────────────────

    public function test_sync_unversioned_media_does_nothing_when_no_media(): void
    {
        $doc = $this->makeDocument();

        // No media attached to the document → no versions should be created
        $this->service->syncUnversionedMedia($doc);

        $this->assertSame(0, $doc->versions()->count());
        $doc->refresh();
        $this->assertNull($doc->version_courante_id);
    }

    public function test_sync_does_not_duplicate_existing_version_records(): void
    {
        $doc = $this->makeDocument();

        // Create a version record (media_id null = no actual file, still valid)
        $version = $this->makeVersion($doc, ['media_id' => null, 'numero_version' => '1.0']);
        $doc->update(['version_courante_id' => $version->id]);

        // Document has no actual media in Spatie library,
        // so syncUnversionedMedia finds nothing new to process.
        $this->service->syncUnversionedMedia($doc);

        // Version count must remain at 1
        $this->assertSame(1, $doc->versions()->count());
    }
}
