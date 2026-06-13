<?php

namespace Tests\Feature;

use App\Models\Correspondant;
use App\Models\Courrier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourrierIndexationTest extends TestCase
{
    use RefreshDatabase;

    // ── Canal ────────────────────────────────────────────────────────────────

    public function test_courrier_has_physique_canal_by_default(): void
    {
        $courrier = $this->makeCourrier();

        $this->assertEquals('Physique', $courrier->canal);
    }

    public function test_courrier_canal_can_be_email(): void
    {
        $courrier = $this->makeCourrier(['canal' => 'Email']);

        $this->assertEquals('Email', $courrier->canal);
    }

    // ── Nature ───────────────────────────────────────────────────────────────

    public function test_nature_courrier_is_nullable_by_default(): void
    {
        $courrier = $this->makeCourrier();

        $this->assertNull($courrier->nature_courrier);
    }

    public function test_nature_courrier_can_be_set(): void
    {
        $courrier = $this->makeCourrier(['nature_courrier' => 'Circulaire']);

        $this->assertEquals('Circulaire', $courrier->nature_courrier);
    }

    // ── Mots-clés (indexation) ────────────────────────────────────────────────

    public function test_mots_cles_array_returns_empty_when_null(): void
    {
        $courrier = $this->makeCourrier(['mots_cles' => null]);

        $this->assertSame([], $courrier->mots_cles_array);
    }

    public function test_mots_cles_array_parses_comma_separated_string(): void
    {
        $courrier = $this->makeCourrier(['mots_cles' => 'budget, marché , décret']);

        $this->assertCount(3, $courrier->mots_cles_array);
        $this->assertContains('budget', $courrier->mots_cles_array);
        $this->assertContains('marché', $courrier->mots_cles_array);
        $this->assertContains('décret', $courrier->mots_cles_array);
    }

    // ── Délai de réponse & retard ─────────────────────────────────────────────

    public function test_is_en_retard_when_deadline_passed_and_not_treated(): void
    {
        $courrier = $this->makeCourrier([
            'statut'        => 'En cours',
            'delai_reponse' => now()->subDay()->toDateString(),
        ]);

        $this->assertTrue($courrier->isEnRetard());
    }

    public function test_is_not_en_retard_when_deadline_in_future(): void
    {
        $courrier = $this->makeCourrier([
            'statut'        => 'En cours',
            'delai_reponse' => now()->addDays(5)->toDateString(),
        ]);

        $this->assertFalse($courrier->isEnRetard());
    }

    public function test_is_not_en_retard_when_courrier_is_traite(): void
    {
        $courrier = $this->makeCourrier([
            'statut'        => 'Traité',
            'delai_reponse' => now()->subDay()->toDateString(),
        ]);

        $this->assertFalse($courrier->isEnRetard());
    }

    public function test_is_not_en_retard_when_deadline_is_null(): void
    {
        $courrier = $this->makeCourrier([
            'statut'        => 'En cours',
            'delai_reponse' => null,
        ]);

        $this->assertFalse($courrier->isEnRetard());
    }

    // ── Accusé de réception ───────────────────────────────────────────────────

    public function test_accuse_reception_defaults_to_false(): void
    {
        $courrier = $this->makeCourrier();

        $this->assertFalse($courrier->accuse_reception);
    }

    public function test_accuse_reception_can_be_set_with_date(): void
    {
        $courrier = $this->makeCourrier([
            'type'             => 'Entrant',
            'accuse_reception' => true,
            'date_accuse'      => now(),
        ]);

        $this->assertTrue($courrier->accuse_reception);
        $this->assertNotNull($courrier->date_accuse);
    }

    // ── Numérisation ──────────────────────────────────────────────────────────

    public function test_scan_status_defaults_to_non_numerise(): void
    {
        $courrier = $this->makeCourrier(['canal' => 'Physique']);

        $this->assertEquals('Non numérisé', $courrier->scan_status);
    }

    public function test_scan_status_can_be_updated_to_numerise(): void
    {
        $scanner = User::factory()->create();
        $courrier = $this->makeCourrier(['canal' => 'Physique']);

        $courrier->update([
            'scan_status'       => 'Numérisé',
            'date_numerisation' => today()->toDateString(),
            'numerise_par'      => $scanner->id,
        ]);

        $this->assertEquals('Numérisé', $courrier->fresh()->scan_status);
        $this->assertNotNull($courrier->fresh()->date_numerisation);
    }

    public function test_numerise_par_belongs_to_user(): void
    {
        $scanner = User::factory()->create();
        $courrier = $this->makeCourrier([
            'canal'             => 'Physique',
            'scan_status'       => 'Numérisé',
            'date_numerisation' => today()->toDateString(),
            'numerise_par'      => $scanner->id,
        ]);

        $this->assertTrue($courrier->numerisePar->is($scanner));
    }

    public function test_scan_status_nullable_for_non_physique(): void
    {
        // For Email/Portal, scan_status is still stored but has no operational meaning
        $courrier = $this->makeCourrier([
            'canal'       => 'Email',
            'scan_status' => 'Non numérisé',
        ]);

        $this->assertEquals('Non numérisé', $courrier->scan_status);
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function makeCourrier(array $overrides = []): Courrier
    {
        $user         = User::factory()->create();
        $correspondant = Correspondant::query()->create([
            'nom_structure' => 'Structure Test Indexation',
        ]);

        return Courrier::query()->create(array_merge([
            'type'                   => 'Entrant',
            'canal'                  => 'Physique',
            'reference'              => 'IDX-' . uniqid(),
            'date_reception_envoi'   => now()->toDateString(),
            'objet'                  => 'Courrier test indexation',
            'priorite'               => 'Normale',
            'statut'                 => 'Nouveau',
            'niveau_confidentialite' => 'Standard',
            'correspondant_id'       => $correspondant->id,
            'user_id'                => $user->id,
            'requires_approval'      => false,
            'approval_status'        => 'not_required',
        ], $overrides));
    }
}
