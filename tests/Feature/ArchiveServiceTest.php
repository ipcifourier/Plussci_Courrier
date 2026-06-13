<?php

namespace Tests\Feature;

use App\Models\ArchiveRecord;
use App\Models\Document;
use App\Models\User;
use App\Services\ArchiveService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchiveServiceTest extends TestCase
{
    use RefreshDatabase;

    private ArchiveService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ArchiveService::class);
        $this->user    = User::factory()->create();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private static int $seq = 0;

    private function makeDocument(array $overrides = []): Document
    {
        self::$seq++;
        return Document::create(array_merge([
            'reference_doc'         => 'ARC-' . self::$seq,
            'titre'                 => 'Archive test doc ' . self::$seq,
            'type_document'         => 'Rapport activite',
            'etat_cycle_vie'        => 'Valide',
            'auteur_id'             => $this->user->id,
            'confidentiality_level' => 'Standard',
        ], $overrides));
    }

    // ─── retentionYearsForType() ──────────────────────────────────────────────

    public function test_retention_years_for_contract_is_10(): void
    {
        $this->assertSame(10, $this->service->retentionYearsForType('Contrat'));
    }

    public function test_retention_years_for_note_service_is_5(): void
    {
        $this->assertSame(5, $this->service->retentionYearsForType('Note service'));
    }

    public function test_retention_years_defaults_to_5_for_unknown_type(): void
    {
        $this->assertSame(5, $this->service->retentionYearsForType('Type inconnu'));
    }

    public function test_retention_years_for_decision_is_10(): void
    {
        $this->assertSame(10, $this->service->retentionYearsForType('Decision'));
    }

    // ─── archiveDocument() ────────────────────────────────────────────────────

    public function test_archive_document_creates_archive_record(): void
    {
        $doc = $this->makeDocument();

        $record = $this->service->archiveDocument($doc, $this->user, 'Test reason', 'Law 123', 7);

        $this->assertInstanceOf(ArchiveRecord::class, $record);
        $this->assertEquals($doc->id, $record->document_id);
        $this->assertEquals($this->user->id, $record->archived_by);
        $this->assertEquals('Test reason', $record->reason);
        $this->assertEquals('Law 123', $record->legal_basis);
        $this->assertEquals(7, $record->retention_years);
    }

    public function test_archive_document_sets_etat_cycle_vie_to_archive(): void
    {
        $doc = $this->makeDocument(['etat_cycle_vie' => 'Valide']);

        $this->service->archiveDocument($doc, $this->user);

        $doc->refresh();
        $this->assertEquals('Archive', $doc->etat_cycle_vie);
    }

    public function test_archive_document_computes_integrity_checksum(): void
    {
        $doc = $this->makeDocument();

        $record = $this->service->archiveDocument($doc, $this->user);

        $this->assertNotNull($record->integrity_checksum);
        $this->assertEquals(64, strlen($record->integrity_checksum)); // SHA-256 hex = 64 chars
    }

    public function test_archive_document_sets_retention_expires_at(): void
    {
        $doc = $this->makeDocument();

        $record = $this->service->archiveDocument($doc, $this->user, '', '', 5);

        $this->assertNotNull($record->retention_expires_at);
        $expected = Carbon::now()->addYears(5)->toDateString();
        $this->assertEquals($expected, $record->retention_expires_at->toDateString());
    }

    public function test_archive_document_uses_type_default_retention_when_none_given(): void
    {
        $doc = $this->makeDocument(['type_document' => 'Facture']);

        $record = $this->service->archiveDocument($doc, $this->user);

        $this->assertEquals(10, $record->retention_years);
    }

    public function test_archive_document_stores_metadata_snapshot(): void
    {
        $doc = $this->makeDocument(['titre' => 'Snapshot Test']);

        $record = $this->service->archiveDocument($doc, $this->user);

        $this->assertNotNull($record->manifest_json);
        $this->assertEquals('Snapshot Test', $record->manifest_json['titre'] ?? null);
        $this->assertEquals('Archive', $record->manifest_json['etat_cycle_vie'] ?? null);
    }

    public function test_archive_document_idempotent_on_already_archived_doc(): void
    {
        $doc = $this->makeDocument(['etat_cycle_vie' => 'Archive']);

        $record1 = $this->service->archiveDocument($doc, $this->user, 'First');
        $record2 = $this->service->archiveDocument($doc, $this->user, 'Second');

        // Should update the same ArchiveRecord, not create a second one
        $this->assertEquals($record1->id, $record2->id);
        $count = ArchiveRecord::where('document_id', $doc->id)->count();
        $this->assertEquals(1, $count);
    }

    // ─── verifyIntegrity() ────────────────────────────────────────────────────

    public function test_verify_integrity_marks_record_as_verified_when_checksum_matches(): void
    {
        $doc    = $this->makeDocument();
        $record = $this->service->archiveDocument($doc, $this->user);

        // Recompute — checksums should match (no media, same seed)
        $status = $this->service->verifyIntegrity($record, $this->user);

        $this->assertEquals('verified', $status);
        $record->refresh();
        $this->assertEquals('verified', $record->integrity_status);
        $this->assertEquals($this->user->id, $record->verified_by);
        $this->assertNotNull($record->verified_at);
    }

    public function test_verify_integrity_marks_record_as_corrupted_when_checksum_differs(): void
    {
        $doc    = $this->makeDocument();
        $record = $this->service->archiveDocument($doc, $this->user);

        // Tamper: change the stored checksum
        $record->update(['integrity_checksum' => str_repeat('a', 64)]);

        $status = $this->service->verifyIntegrity($record, $this->user);

        $this->assertEquals('corrupted', $status);
        $record->refresh();
        $this->assertEquals('corrupted', $record->integrity_status);
    }

    // ─── generateManifestJson() ───────────────────────────────────────────────

    public function test_generate_manifest_json_includes_all_records(): void
    {
        $doc1 = $this->makeDocument();
        $doc2 = $this->makeDocument();

        $r1 = $this->service->archiveDocument($doc1, $this->user);
        $r2 = $this->service->archiveDocument($doc2, $this->user);

        $records  = ArchiveRecord::with(['document', 'archivedBy', 'verifiedBy'])->get();
        $manifest = $this->service->generateManifestJson($records);

        $this->assertArrayHasKey('generated_at', $manifest);
        $this->assertArrayHasKey('records', $manifest);
        $this->assertCount(2, $manifest['records']);
    }

    public function test_generate_manifest_json_contains_expected_fields(): void
    {
        $doc    = $this->makeDocument(['reference_doc' => 'MANIFEST-001']);
        $record = $this->service->archiveDocument($doc, $this->user, 'Test', 'Law X');

        $records  = ArchiveRecord::with(['document', 'archivedBy', 'verifiedBy'])->get();
        $manifest = $this->service->generateManifestJson($records);
        $entry    = $manifest['records'][0];

        $this->assertEquals('MANIFEST-001', $entry['reference_doc']);
        $this->assertArrayHasKey('integrity_checksum', $entry);
        $this->assertArrayHasKey('retention_expires_at', $entry);
        $this->assertArrayHasKey('legal_basis', $entry);
    }

    // ─── ArchiveRecord helpers ────────────────────────────────────────────────

    public function test_archive_record_is_expired_when_past_retention_date(): void
    {
        $doc    = $this->makeDocument();
        $record = $this->service->archiveDocument($doc, $this->user, '', '', 1);

        // Force past expiry date
        $record->update(['retention_expires_at' => Carbon::now()->subDay()->toDateString()]);

        $this->assertTrue($record->isExpired());
    }

    public function test_archive_record_is_not_expired_when_within_retention(): void
    {
        $doc    = $this->makeDocument();
        $record = $this->service->archiveDocument($doc, $this->user, '', '', 10);

        $this->assertFalse($record->isExpired());
    }
}
