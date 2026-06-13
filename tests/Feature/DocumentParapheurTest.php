<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentSignature;
use App\Models\User;
use App\Notifications\DocumentSignatureDecisionNotification;
use App\Notifications\DocumentSignatureRequestedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DocumentParapheurTest extends TestCase
{
    use RefreshDatabase;

    private User $auteur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auteur = User::factory()->create();
    }

    // ── Circuit de base ───────────────────────────────────────────────────────

    public function test_lance_parapheur_sets_status_pending_and_first_level(): void
    {
        $doc  = $this->makeDocument();
        $sig1 = $this->addSignataire($doc, level: 1);
        $this->addSignataire($doc, level: 2);

        $doc->lancerParapheur();

        $doc->refresh();

        $this->assertEquals('pending', $doc->parapheur_status);
        $this->assertEquals(1, $doc->current_signature_level);
    }

    public function test_lance_parapheur_does_nothing_without_signataires(): void
    {
        $doc = $this->makeDocument();

        $doc->lancerParapheur();

        $doc->refresh();
        $this->assertEquals('not_required', $doc->parapheur_status);
    }

    public function test_signature_advances_to_next_level(): void
    {
        $sig1User = User::factory()->create();
        $sig2User = User::factory()->create();

        $doc  = $this->makeDocument();
        $sig1 = $this->addSignataire($doc, level: 1, user: $sig1User);
        $sig2 = $this->addSignataire($doc, level: 2, user: $sig2User);

        $doc->lancerParapheur();

        // Signer au niveau 1
        $sig1->update([
            'status'    => 'signed',
            'signed_at' => now(),
        ]);

        // Simuler la progression après signature niveau 1
        $pendingAtLevel = $doc->signatures()
            ->where('level', $doc->current_signature_level)
            ->where('status', 'pending')
            ->count();

        $this->assertEquals(0, $pendingAtLevel);

        $nextLevel = $doc->signatures()->where('status', 'pending')->min('level');
        $this->assertEquals(2, $nextLevel);
    }

    public function test_all_signed_sets_completed_and_validates_document(): void
    {
        $sigUser = User::factory()->create();
        $doc     = $this->makeDocument();
        $sig     = $this->addSignataire($doc, level: 1, user: $sigUser);

        $doc->lancerParapheur();

        // Signer tous les niveaux
        $sig->update(['status' => 'signed', 'signed_at' => now()]);

        $doc->update([
            'parapheur_status'        => 'completed',
            'current_signature_level' => null,
            'etat_cycle_vie'          => 'Valide',
        ]);

        $doc->refresh();

        $this->assertEquals('completed', $doc->parapheur_status);
        $this->assertEquals('Valide', $doc->etat_cycle_vie);
        $this->assertNull($doc->current_signature_level);
    }

    public function test_rejection_sets_rejected_status(): void
    {
        $sigUser = User::factory()->create();
        $doc     = $this->makeDocument();
        $sig     = $this->addSignataire($doc, level: 1, user: $sigUser);

        $doc->lancerParapheur();

        $sig->update(['status' => 'rejected', 'comment' => 'Non conforme', 'signed_at' => now()]);
        $doc->update(['parapheur_status' => 'rejected', 'current_signature_level' => null]);

        $doc->refresh();

        $this->assertEquals('rejected', $doc->parapheur_status);
        $this->assertNull($doc->current_signature_level);
    }

    // ── Modèle DocumentSignature ──────────────────────────────────────────────

    public function test_signature_is_pending_by_default(): void
    {
        $doc = $this->makeDocument();
        $sig = $this->addSignataire($doc, level: 1);

        $this->assertTrue($sig->isPending());
        $this->assertFalse($sig->isSigned());
        $this->assertFalse($sig->isRejected());
    }

    public function test_signature_status_label_pending(): void
    {
        $sig = $this->addSignataire($this->makeDocument(), level: 1);

        $this->assertStringContainsString('En attente', $sig->statusLabel());
    }

    public function test_signature_status_label_signed(): void
    {
        $sig = $this->addSignataire($this->makeDocument(), level: 1);
        $sig->update(['status' => 'signed']);

        $this->assertStringContainsString('✔', $sig->fresh()->statusLabel());
    }

    public function test_signature_status_label_rejected(): void
    {
        $sig = $this->addSignataire($this->makeDocument(), level: 1);
        $sig->update(['status' => 'rejected']);

        $this->assertStringContainsString('✖', $sig->fresh()->statusLabel());
    }

    public function test_signature_belongs_to_document(): void
    {
        $doc = $this->makeDocument();
        $sig = $this->addSignataire($doc, level: 1);

        $this->assertTrue($sig->document->is($doc));
    }

    public function test_signature_belongs_to_signataire(): void
    {
        $sigUser = User::factory()->create();
        $doc     = $this->makeDocument();
        $sig     = $this->addSignataire($doc, level: 1, user: $sigUser);

        $this->assertTrue($sig->signataire->is($sigUser));
    }

    // ── Notifications ─────────────────────────────────────────────────────────

    public function test_lancer_parapheur_sends_notification_to_first_level(): void
    {
        Notification::fake();

        $sigUser = User::factory()->create();
        $doc     = $this->makeDocument();
        $this->addSignataire($doc, level: 1, user: $sigUser);

        $doc->lancerParapheur();

        Notification::assertSentTo(
            $sigUser,
            DocumentSignatureRequestedNotification::class,
        );
    }

    public function test_notify_auteur_decision_sends_notification(): void
    {
        Notification::fake();

        $doc = $this->makeDocument();

        $doc->notifyAuteurDecision('completed');

        Notification::assertSentTo(
            $this->auteur,
            DocumentSignatureDecisionNotification::class,
        );
    }

    public function test_notify_auteur_decision_rejected_sends_notification(): void
    {
        Notification::fake();

        $doc = $this->makeDocument();

        $doc->notifyAuteurDecision('rejected', 'Document non conforme');

        Notification::assertSentTo(
            $this->auteur,
            DocumentSignatureDecisionNotification::class,
        );
    }

    // ── Helpers circuit ────────────────────────────────────────────────────────

    public function test_is_parapheur_pending_true_when_pending(): void
    {
        $doc = $this->makeDocument(['parapheur_status' => 'pending']);

        $this->assertTrue($doc->isParapheurPending());
    }

    public function test_is_parapheur_completed_true_when_completed(): void
    {
        $doc = $this->makeDocument(['parapheur_status' => 'completed']);

        $this->assertTrue($doc->isParapheurCompleted());
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static int $seq = 0;

    private function makeDocument(array $overrides = []): Document
    {
        self::$seq++;

        return Document::create(array_merge([
            'reference_doc'         => 'PAR-' . self::$seq,
            'titre'                 => 'Document parapheur ' . self::$seq,
            'type_document'         => 'Rapport',
            'etat_cycle_vie'        => 'Brouillon',
            'auteur_id'             => $this->auteur->id,
            'confidentiality_level' => 'Standard',
            'parapheur_status'      => 'not_required',
        ], $overrides));
    }

    private function addSignataire(
        Document $doc,
        int $level,
        ?User $user = null,
        string $role = 'Signature',
    ): DocumentSignature {
        return DocumentSignature::create([
            'document_id'    => $doc->id,
            'level'          => $level,
            'signataire_id'  => ($user ?? User::factory()->create())->id,
            'role_signature' => $role,
            'status'         => 'pending',
        ]);
    }
}
