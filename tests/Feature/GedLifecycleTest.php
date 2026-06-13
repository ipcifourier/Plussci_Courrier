<?php

namespace Tests\Feature;

use App\Models\Courrier;
use App\Models\Correspondant;
use App\Models\Document;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GedLifecycleTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ────────────────────────────────────────────────────────────────

    private function makeDocument(User $user, string $etat = 'Brouillon'): Document
    {
        static $seq = 0;
        $seq++;

        return Document::query()->create([
            'reference_doc'       => 'DOC-LIFECYCLE-' . $seq,
            'titre'               => 'Document test ' . $seq,
            'type_document'       => 'Note',
            'etat_cycle_vie'      => $etat,
            'auteur_id'           => $user->id,
            'confidentiality_level' => 'Standard',
        ]);
    }

    private function makeTraiteCourrier(User $user): Courrier
    {
        $correspondant = Correspondant::query()->firstOrCreate(
            ['nom_structure' => 'Org Archive'],
            [],
        );

        return Courrier::query()->create([
            'type'                   => 'Entrant',
            'reference'              => 'REF-ARC-' . uniqid(),
            'date_reception_envoi'   => now()->subDays(200)->toDateString(),
            'objet'                  => 'À archiver',
            'priorite'               => 'Normale',
            'statut'                 => 'Traité',
            'niveau_confidentialite' => 'Standard',
            'correspondant_id'       => $correspondant->id,
            'user_id'                => $user->id,
        ]);
    }

    private function setUpdatedAt(string $table, int $id, Carbon $date): void
    {
        DB::table($table)->where('id', $id)->update(['updated_at' => $date]);
    }

    // ─── ged:archive-stale ───────────────────────────────────────────────────────

    public function test_archive_stale_archives_old_validated_documents(): void
    {
        $user = User::factory()->create();

        $stale  = $this->makeDocument($user, 'Valide');
        $recent = $this->makeDocument($user, 'Valide');

        // Push one document past the custom threshold of 30 days
        $this->setUpdatedAt('documents', $stale->id, now()->subDays(40));

        $this->artisan('ged:archive-stale', ['--days' => 30])
            ->assertSuccessful();

        $this->assertEquals('Archive', $stale->fresh()->etat_cycle_vie);
        $this->assertEquals('Valide', $recent->fresh()->etat_cycle_vie);
    }

    public function test_archive_stale_does_not_touch_brouillon_documents(): void
    {
        $user = User::factory()->create();

        $brouillon = $this->makeDocument($user, 'Brouillon');
        $this->setUpdatedAt('documents', $brouillon->id, now()->subDays(400));

        $this->artisan('ged:archive-stale', ['--days' => 30])
            ->assertSuccessful();

        $this->assertEquals('Brouillon', $brouillon->fresh()->etat_cycle_vie);
    }

    public function test_archive_stale_dry_run_does_not_modify_documents(): void
    {
        $user = User::factory()->create();

        $doc = $this->makeDocument($user, 'Valide');
        $this->setUpdatedAt('documents', $doc->id, now()->subDays(400));

        $this->artisan('ged:archive-stale', ['--days' => 30, '--dry-run' => true])
            ->assertSuccessful();

        $this->assertEquals('Valide', $doc->fresh()->etat_cycle_vie);
    }

    public function test_archive_stale_creates_audit_log(): void
    {
        $user = User::factory()->create();

        $doc = $this->makeDocument($user, 'Valide');
        $this->setUpdatedAt('documents', $doc->id, now()->subDays(400));

        $this->artisan('ged:archive-stale', ['--days' => 30])
            ->assertSuccessful();

        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'ged.document.auto_archived',
            'entity_type' => Document::class,
            'entity_id'   => $doc->id,
        ]);
    }

    public function test_archive_stale_outputs_no_documents_when_none_eligible(): void
    {
        User::factory()->create();

        $this->artisan('ged:archive-stale', ['--days' => 30])
            ->expectsOutput('Aucun document à archiver.')
            ->assertSuccessful();
    }

    // ─── courrier:auto-archive ───────────────────────────────────────────────────

    public function test_courrier_auto_archive_archives_old_traite_courriers(): void
    {
        $user    = User::factory()->create();
        $courrier = $this->makeTraiteCourrier($user);

        $this->setUpdatedAt('courriers', $courrier->id, now()->subDays(100));

        $this->artisan('courrier:auto-archive', ['--days' => 90])
            ->assertSuccessful();

        $this->assertEquals('Archivé', $courrier->fresh()->statut);
    }

    public function test_courrier_auto_archive_skips_recent_traite_courriers(): void
    {
        $user    = User::factory()->create();
        $courrier = $this->makeTraiteCourrier($user);

        // updated_at set to 30 days ago — under 90-day threshold
        $this->setUpdatedAt('courriers', $courrier->id, now()->subDays(30));

        $this->artisan('courrier:auto-archive', ['--days' => 90])
            ->assertSuccessful();

        $this->assertEquals('Traité', $courrier->fresh()->statut);
    }

    public function test_courrier_auto_archive_dry_run_does_not_modify(): void
    {
        $user    = User::factory()->create();
        $courrier = $this->makeTraiteCourrier($user);

        $this->setUpdatedAt('courriers', $courrier->id, now()->subDays(200));

        $this->artisan('courrier:auto-archive', ['--days' => 90, '--dry-run' => true])
            ->assertSuccessful();

        $this->assertEquals('Traité', $courrier->fresh()->statut);
    }

    public function test_courrier_auto_archive_creates_audit_log(): void
    {
        $user    = User::factory()->create();
        $courrier = $this->makeTraiteCourrier($user);

        $this->setUpdatedAt('courriers', $courrier->id, now()->subDays(200));

        $this->artisan('courrier:auto-archive', ['--days' => 90])
            ->assertSuccessful();

        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'courrier.auto_archived',
            'entity_type' => Courrier::class,
            'entity_id'   => $courrier->id,
        ]);
    }
}
