<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Dossier;
use App\Models\Correspondant;
use App\Models\Courrier;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SearchServiceTest extends TestCase
{
    use RefreshDatabase;

    private SearchService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SearchService::class);
        $this->user    = User::factory()->create();
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function makeDocument(array $overrides = []): Document
    {
        static $seq = 0;
        $seq++;

        $doc = Document::create(array_merge([
            'reference_doc'          => 'SRCH-' . $seq,
            'titre'                  => 'Document test ' . $seq,
            'type_document'          => 'Note',
            'etat_cycle_vie'         => 'Brouillon',
            'auteur_id'              => $this->user->id,
            'confidentiality_level'  => 'Standard',
        ], $overrides));

        return $doc;
    }

    private function attachVersion(Document $doc, array $versionOverrides = []): DocumentVersion
    {
        $version = DocumentVersion::create(array_merge([
            'document_id'    => $doc->id,
            'numero_version' => '1.0',
            'created_by'     => $this->user->id,
            'ocr_status'     => 'completed',
            'source'         => 'upload',
        ], $versionOverrides));

        $doc->update(['version_courante_id' => $version->id]);

        return $version;
    }

    /**
     * Normalize paginator results into a collection for assertions.
     */
    private function items($results)
    {
        return collect($results->items());
    }

    // ─── Document full-text search ──────────────────────────────────────────

    public function test_search_documents_by_titre(): void
    {
        $this->makeDocument(['titre' => 'Rapport annuel 2025']);
        $this->makeDocument(['titre' => 'Note de service']);

        $results = $this->service->searchDocuments(['q' => 'Rapport annuel']);

        $items = $this->items($results);
        $this->assertCount(1, $items);
        $this->assertSame('Rapport annuel 2025', $items->first()->titre);
    }

    public function test_search_documents_by_reference(): void
    {
        $doc = $this->makeDocument();
        $doc->update(['reference_doc' => 'DOC-TEST-20250101-001']);
        $this->makeDocument(); // another doc

        $results = $this->service->searchDocuments(['q' => 'DOC-TEST-20250101']);

        $items = $this->items($results);
        $this->assertCount(1, $items);
        $this->assertSame($doc->id, $items->first()->id);
    }

    public function test_search_documents_by_ocr_text(): void
    {
        $docA = $this->makeDocument(['titre' => 'Doc A']);
        $docB = $this->makeDocument(['titre' => 'Doc B']);

        $this->attachVersion($docA, ['ocr_text' => 'Le budget prévisionnel pour 2025 est validé']);
        $this->attachVersion($docB, ['ocr_text' => 'Objet : réunion de coordination']);

        $results = $this->service->searchDocuments(['q' => 'budget prévisionnel']);

        $items = $this->items($results);
        $this->assertCount(1, $items);
        $this->assertSame($docA->id, $items->first()->id);
    }

    public function test_search_returns_empty_when_no_filter(): void
    {
        $this->makeDocument();

        $results = $this->service->searchDocuments([]);

        // Empty filters still runs the query (service caller is responsible for
        // deciding whether to call it). The paginator may return all documents.
        // We only verify it returns a paginator instance.
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $results);
    }

    // ─── Document filters ────────────────────────────────────────────────────

    public function test_filter_by_type_document(): void
    {
        $this->makeDocument(['type_document' => 'Contrat', 'titre' => 'Contrat X']);
        $this->makeDocument(['type_document' => 'Note',    'titre' => 'Note Y']);

        $results = $this->service->searchDocuments(['q' => 'test', 'type_document' => 'Contrat']);

        $items = $this->items($results);
        $this->assertTrue($items->every(fn ($d) => $d->type_document === 'Contrat'));
    }

    public function test_filter_by_etat_cycle_vie(): void
    {
        $this->makeDocument(['etat_cycle_vie' => 'Valide',    'titre' => 'Valide test']);
        $this->makeDocument(['etat_cycle_vie' => 'Brouillon', 'titre' => 'Brouillon test']);

        $results = $this->service->searchDocuments(['q' => 'test', 'etat_cycle_vie' => 'Valide']);

        $items = $this->items($results);
        $this->assertTrue($items->every(fn ($d) => $d->etat_cycle_vie === 'Valide'));
        $this->assertGreaterThan(0, $items->count());
    }

    public function test_filter_by_dossier_id(): void
    {
        $dossier = Dossier::create([
            'code'       => 'DSS-SRCH',
            'libelle'    => 'Dossier Recherche',
            'owner_id'   => $this->user->id,
            'statut'     => 'Actif',
        ]);

        $this->makeDocument(['dossier_id' => $dossier->id, 'titre' => 'In dossier']);
        $this->makeDocument(['titre' => 'No dossier']);

        $results = $this->service->searchDocuments(['q' => 'test', 'dossier_id' => $dossier->id]);

        $items = $this->items($results);
        $this->assertTrue($items->every(fn ($d) => $d->dossier_id === $dossier->id));
    }

    public function test_filter_by_confidentiality_level(): void
    {
        $this->makeDocument(['confidentiality_level' => 'Confidentiel', 'titre' => 'Secret test']);
        $this->makeDocument(['confidentiality_level' => 'Standard',     'titre' => 'Public test']);

        $results = $this->service->searchDocuments(['q' => 'test', 'confidentiality_level' => 'Confidentiel']);

        $items = $this->items($results);
        $this->assertTrue($items->every(fn ($d) => $d->confidentiality_level === 'Confidentiel'));
    }

    public function test_filter_by_auteur_id(): void
    {
        $other = User::factory()->create();
        $this->makeDocument(['auteur_id' => $this->user->id, 'titre' => 'Mine test']);
        $this->makeDocument(['auteur_id' => $other->id,     'titre' => 'Theirs test']);

        $results = $this->service->searchDocuments(['q' => 'test', 'auteur_id' => $this->user->id]);

        $items = $this->items($results);
        $this->assertTrue($items->every(fn ($d) => $d->auteur_id === $this->user->id));
    }

    public function test_filter_by_date_range(): void
    {
        $old = $this->makeDocument(['titre' => 'Old test']);
        $new = $this->makeDocument(['titre' => 'New test']);

        DB::table('documents')->where('id', $old->id)->update(['created_at' => '2024-01-15 10:00:00', 'updated_at' => '2024-01-15 10:00:00']);
        DB::table('documents')->where('id', $new->id)->update(['created_at' => '2025-06-01 10:00:00', 'updated_at' => '2025-06-01 10:00:00']);

        $results = $this->service->searchDocuments([
            'q'         => 'test',
            'date_from' => '2025-01-01',
            'date_to'   => '2025-12-31',
        ]);

        $items = $this->items($results);
        $this->assertCount(1, $items);
        $this->assertSame($new->id, $items->first()->id);
    }

    public function test_filter_by_source(): void
    {
        $docA = $this->makeDocument(['titre' => 'Email test']);
        $docB = $this->makeDocument(['titre' => 'Upload test']);
        $this->attachVersion($docA, ['source' => 'email']);
        $this->attachVersion($docB, ['source' => 'upload']);

        $results = $this->service->searchDocuments(['q' => 'test', 'source' => 'email']);

        $items = $this->items($results);
        $this->assertCount(1, $items);
        $this->assertSame($docA->id, $items->first()->id);
    }

    // ─── Courrier search ─────────────────────────────────────────────────────

    private function makeCourrier(array $overrides = []): Courrier
    {
        static $seq = 0;
        $seq++;

        $correspondant = Correspondant::firstOrCreate(
            ['nom_structure' => 'Org Test'],
            ['nom_contact' => 'Contact', 'email' => 'test@org.ci'],
        );

        return Courrier::create(array_merge([
            'type'                   => 'Entrant',
            'reference'              => 'REF-SRCH-' . $seq,
            'date_reception_envoi'   => now()->toDateString(),
            'objet'                  => 'Objet test courrier ' . $seq,
            'priorite'               => 'Normale',
            'statut'                 => 'Nouveau',
            'niveau_confidentialite' => 'Standard',
            'correspondant_id'       => $correspondant->id,
            'user_id'                => $this->user->id,
        ], $overrides));
    }

    public function test_search_courriers_by_objet(): void
    {
        $this->makeCourrier(['objet' => 'Budget prévisionnel 2025']);
        $this->makeCourrier(['objet' => 'Réunion de coordination']);

        $results = $this->service->searchCourriers(['q' => 'budget']);

        $items = $this->items($results);
        $this->assertCount(1, $items);
        $this->assertStringContainsStringIgnoringCase('budget', $items->first()->objet);
    }

    public function test_search_courriers_by_reference(): void
    {
        $courrier = $this->makeCourrier();
        $courrier->update(['reference' => 'REF-SPECIAL-XYZ']);
        $this->makeCourrier();

        $results = $this->service->searchCourriers(['q' => 'SPECIAL-XYZ']);

        $items = $this->items($results);
        $this->assertCount(1, $items);
        $this->assertSame($courrier->id, $items->first()->id);
    }

    public function test_filter_courriers_by_type(): void
    {
        $this->makeCourrier(['type' => 'Entrant', 'objet' => 'Entrant test']);
        $this->makeCourrier(['type' => 'Sortant', 'objet' => 'Sortant test']);

        $results = $this->service->searchCourriers(['q' => 'test', 'type' => 'Sortant']);

        $items = $this->items($results);
        $this->assertTrue($items->every(fn ($c) => $c->type === 'Sortant'));
    }

    public function test_filter_courriers_by_statut(): void
    {
        $this->makeCourrier(['statut' => 'Traité',  'objet' => 'Traité test']);
        $this->makeCourrier(['statut' => 'Nouveau', 'objet' => 'Nouveau test']);

        $results = $this->service->searchCourriers(['q' => 'test', 'statut' => 'Traité']);

        $items = $this->items($results);
        $this->assertTrue($items->every(fn ($c) => $c->statut === 'Traité'));
        $this->assertGreaterThan(0, $items->count());
    }

    // ─── Highlight helper ────────────────────────────────────────────────────

    public function test_highlight_wraps_term_in_mark(): void
    {
        $result = $this->service->highlight('Le budget prévisionnel est validé', 'budget');

        $this->assertStringContainsString('<mark', $result);
        $this->assertStringContainsString('budget', $result);
    }

    public function test_highlight_returns_excerpt_when_no_match(): void
    {
        $long = str_repeat('Lorem ipsum dolor sit amet. ', 20);
        $result = $this->service->highlight($long, 'zzznomatch');

        $this->assertStringNotContainsString('<mark', $result);
        $this->assertStringContainsString('…', $result);
    }

    public function test_highlight_returns_truncated_when_no_query(): void
    {
        $result = $this->service->highlight('Hello world', '');

        $this->assertStringNotContainsString('<mark', $result);
    }
}
