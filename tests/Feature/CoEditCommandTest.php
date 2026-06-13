<?php

namespace Tests\Feature;

use App\Console\Commands\CleanStalePresenceSessions;
use App\Models\Document;
use App\Models\DocumentSession;
use App\Models\User;
use App\Services\DocumentPresenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CoEditCommandTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ────────────────────────────────────────────────────────────────

    private static int $seq = 0;

    private function makeDocument(User $user): Document
    {
        self::$seq++;

        return Document::query()->create([
            'reference_doc'         => 'DOC-CMD-' . self::$seq,
            'titre'                 => 'CoEdit cmd doc ' . self::$seq,
            'type_document'         => 'Note',
            'etat_cycle_vie'        => 'Valide',
            'auteur_id'             => $user->id,
            'confidentiality_level' => 'Standard',
        ]);
    }

    private function staleSession(Document $document, User $user): DocumentSession
    {
        return DocumentSession::create([
            'document_id'  => $document->id,
            'user_id'      => $user->id,
            'mode'         => 'view',
            'last_seen_at' => now()->subMinutes(DocumentPresenceService::TTL_MINUTES + 5),
            'joined_at'    => now()->subMinutes(DocumentPresenceService::TTL_MINUTES + 10),
        ]);
    }

    private function freshSession(Document $document, User $user): DocumentSession
    {
        return DocumentSession::create([
            'document_id'  => $document->id,
            'user_id'      => $user->id,
            'mode'         => 'view',
            'last_seen_at' => now()->subMinutes(1),
            'joined_at'    => now()->subMinutes(2),
        ]);
    }

    // ─── presence:clean command ───────────────────────────────────────────────

    public function test_command_deletes_stale_sessions(): void
    {
        $user     = User::factory()->create();
        $document = $this->makeDocument($user);

        $userB = User::factory()->create();
        $this->staleSession($document, $user);
        $this->staleSession($document, $userB);

        $this->artisan(CleanStalePresenceSessions::class)
            ->expectsOutput('Nettoyage terminé : 2 session(s) expirée(s) supprimée(s).')
            ->assertExitCode(0);

        $this->assertSame(0, DocumentSession::count());
    }

    public function test_command_preserves_fresh_sessions(): void
    {
        $userA    = User::factory()->create();
        $userB    = User::factory()->create();
        $document = $this->makeDocument($userA);

        $this->staleSession($document, $userA);  // stale — should be deleted
        $this->freshSession($document, $userB);  // fresh — must survive

        $this->artisan(CleanStalePresenceSessions::class)
            ->assertExitCode(0);

        $this->assertSame(1, DocumentSession::count());
        $this->assertEquals($userB->id, DocumentSession::first()->user_id);
    }

    public function test_command_returns_zero_when_nothing_to_clean(): void
    {
        $this->artisan(CleanStalePresenceSessions::class)
            ->expectsOutput('Nettoyage terminé : 0 session(s) expirée(s) supprimée(s).')
            ->assertExitCode(0);
    }

    public function test_dry_run_does_not_delete_sessions(): void
    {
        $user     = User::factory()->create();
        $document = $this->makeDocument($user);

        $this->staleSession($document, $user);

        $this->artisan(CleanStalePresenceSessions::class, ['--dry-run' => true])
            ->assertExitCode(0);

        // Session must still exist after a dry-run
        $this->assertSame(1, DocumentSession::count());
    }

    public function test_dry_run_reports_correct_count(): void
    {
        $user     = User::factory()->create();
        $userB    = User::factory()->create();
        $document = $this->makeDocument($user);

        $this->staleSession($document, $user);
        $this->staleSession($document, $userB);

        $this->artisan(CleanStalePresenceSessions::class, ['--dry-run' => true])
            ->expectsOutput('[DRY-RUN] 2 session(s) expirée(s) seraient supprimées (TTL : ' . DocumentPresenceService::TTL_MINUTES . ' min).')
            ->assertExitCode(0);
    }

    public function test_command_handles_mixed_stale_and_fresh(): void
    {
        $users    = User::factory(4)->create();
        $document = $this->makeDocument($users[0]);

        $this->staleSession($document, $users[0]);
        $this->staleSession($document, $users[1]);
        $this->freshSession($document, $users[2]);
        $this->freshSession($document, $users[3]);

        $this->artisan(CleanStalePresenceSessions::class)
            ->expectsOutput('Nettoyage terminé : 2 session(s) expirée(s) supprimée(s).')
            ->assertExitCode(0);

        $this->assertSame(2, DocumentSession::count());
    }

    // ─── DocumentPresenceService cleanup integration ──────────────────────────

    public function test_clean_stale_via_service_returns_count(): void
    {
        $user     = User::factory()->create();
        $document = $this->makeDocument($user);
        $service  = app(DocumentPresenceService::class);

        $this->staleSession($document, $user);

        $deleted = $service->cleanStaleSessions();

        $this->assertSame(1, $deleted);
    }

    public function test_clean_stale_does_not_affect_fresh_sessions(): void
    {
        $user     = User::factory()->create();
        $document = $this->makeDocument($user);
        $service  = app(DocumentPresenceService::class);

        $this->freshSession($document, $user);

        $deleted = $service->cleanStaleSessions();

        $this->assertSame(0, $deleted);
        $this->assertSame(1, DocumentSession::count());
    }

    // ─── Schedule registration (smoke test) ──────────────────────────────────

    public function test_command_is_registered_with_artisan(): void
    {
        $this->assertTrue(
            collect(\Artisan::all())->has('presence:clean'),
            'La commande presence:clean doit être enregistrée dans Artisan.'
        );
    }
}
