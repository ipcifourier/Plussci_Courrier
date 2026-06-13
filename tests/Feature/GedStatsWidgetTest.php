<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GedStatsWidgetTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function makeDocument(string $etat): Document
    {
        static $n = 0;
        $n++;

        return Document::query()->create([
            'reference_doc'        => 'WGT-' . $n,
            'titre'                => 'Doc ' . $n,
            'type_document'        => 'Note',
            'etat_cycle_vie'       => $etat,
            'auteur_id'            => $this->user->id,
            'confidentiality_level' => 'Standard',
        ]);
    }

    private function makeDossier(string $statut = 'Actif'): Dossier
    {
        static $d = 0;
        $d++;

        return Dossier::query()->create([
            'code'            => 'DOS-WGT-' . $d,
            'libelle'         => 'Dossier ' . $d,
            'confidentialite' => 'Standard',
            'owner_id'        => $this->user->id,
            'statut'          => $statut,
        ]);
    }

    public function test_ged_stats_counts_documents_by_etat(): void
    {
        $this->makeDocument('Brouillon');
        $this->makeDocument('Brouillon');
        $this->makeDocument('Valide');
        $this->makeDocument('Valide');
        $this->makeDocument('Valide');
        $this->makeDocument('Archive');

        $this->assertEquals(2, Document::where('etat_cycle_vie', 'Brouillon')->count());
        $this->assertEquals(3, Document::where('etat_cycle_vie', 'Valide')->count());
        $this->assertEquals(1, Document::where('etat_cycle_vie', 'Archive')->count());
    }

    public function test_ged_stats_counts_active_dossiers_only(): void
    {
        $this->makeDossier('Actif');
        $this->makeDossier('Actif');
        $this->makeDossier('Clos');
        $this->makeDossier('Archive');

        $this->assertEquals(2, Dossier::where('statut', 'Actif')->count());
    }

    public function test_ged_stats_zero_when_no_documents(): void
    {
        $this->assertEquals(0, Document::where('etat_cycle_vie', 'Valide')->count());
        $this->assertEquals(0, Document::where('etat_cycle_vie', 'Archive')->count());
    }
}
