<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentSession;
use App\Models\User;
use App\Notifications\DocumentEditedNotification;
use App\Services\DocumentPresenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CoEditTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ────────────────────────────────────────────────────────────────

    private static int $seq = 0;

    private function makeDocument(User $user): Document
    {
        self::$seq++;

        return Document::query()->create([
            'reference_doc'         => 'DOC-CE-' . self::$seq,
            'titre'                 => 'CoEdit doc ' . self::$seq,
            'type_document'         => 'Note',
            'etat_cycle_vie'        => 'Valide',
            'auteur_id'             => $user->id,
            'confidentiality_level' => 'Standard',
        ]);
    }

    private function makeService(): DocumentPresenceService
    {
        return app(DocumentPresenceService::class);
    }

    // ─── DocumentSession model ───────────────────────────────────────────────

    public function test_session_isEditor_returns_true_when_mode_is_edit(): void
    {
        $user     = User::factory()->create();
        $document = $this->makeDocument($user);

        $session = DocumentSession::create([
            'document_id'  => $document->id,
            'user_id'      => $user->id,
            'mode'         => 'edit',
            'last_seen_at' => now(),
            'joined_at'    => now(),
        ]);

        $this->assertTrue($session->isEditor());
        $this->assertFalse($session->isViewer());
    }

    public function test_session_isViewer_returns_true_when_mode_is_view(): void
    {
        $user     = User::factory()->create();
        $document = $this->makeDocument($user);

        $session = DocumentSession::create([
            'document_id'  => $document->id,
            'user_id'      => $user->id,
            'mode'         => 'view',
            'last_seen_at' => now(),
            'joined_at'    => now(),
        ]);

        $this->assertTrue($session->isViewer());
        $this->assertFalse($session->isEditor());
    }

    public function test_session_belongs_to_user_and_document(): void
    {
        $user     = User::factory()->create();
        $document = $this->makeDocument($user);

        $session = DocumentSession::create([
            'document_id'  => $document->id,
            'user_id'      => $user->id,
            'mode'         => 'view',
            'last_seen_at' => now(),
            'joined_at'    => now(),
        ]);

        $this->assertEquals($user->id, $session->user->id);
        $this->assertEquals($document->id, $session->document->id);
    }

    // ─── DocumentPresenceService::join() ────────────────────────────────────

    public function test_join_creates_session_with_correct_mode(): void
    {
        $user     = User::factory()->create();
        $document = $this->makeDocument($user);
        $service  = $this->makeService();

        $session = $service->join($document, $user, 'edit');

        $this->assertInstanceOf(DocumentSession::class, $session);
        $this->assertEquals('edit', $session->mode);
        $this->assertEquals($user->id, $session->user_id);
        $this->assertEquals($document->id, $session->document_id);
    }

    public function test_join_defaults_to_view_mode(): void
    {
        $user     = User::factory()->create();
        $document = $this->makeDocument($user);
        $service  = $this->makeService();

        $session = $service->join($document, $user);

        $this->assertEquals('view', $session->mode);
    }

    public function test_join_updates_existing_session(): void
    {
        $user     = User::factory()->create();
        $document = $this->makeDocument($user);
        $service  = $this->makeService();

        $service->join($document, $user, 'view');
        $service->join($document, $user, 'edit');

        // Must not duplicate rows — still one session per (document, user)
        $this->assertSame(1, DocumentSession::where('document_id', $document->id)
            ->where('user_id', $user->id)
            ->count());

        $this->assertEquals('edit', DocumentSession::where('document_id', $document->id)
            ->where('user_id', $user->id)
            ->value('mode'));
    }

    // ─── DocumentPresenceService::heartbeat() ────────────────────────────────

    public function test_heartbeat_updates_last_seen_at(): void
    {
        $user     = User::factory()->create();
        $document = $this->makeDocument($user);
        $service  = $this->makeService();

        Carbon::setTestNow(Carbon::now()->subMinutes(4));
        $service->join($document, $user, 'view');
        Carbon::setTestNow(null);

        $before = DocumentSession::where('document_id', $document->id)
            ->where('user_id', $user->id)
            ->value('last_seen_at');

        $service->heartbeat($document, $user);

        $after = DocumentSession::where('document_id', $document->id)
            ->where('user_id', $user->id)
            ->value('last_seen_at');

        $this->assertTrue(
            Carbon::parse($after)->gt(Carbon::parse($before)),
            'heartbeat() doit mettre à jour last_seen_at'
        );
    }

    // ─── DocumentPresenceService::leave() ────────────────────────────────────

    public function test_leave_removes_session(): void
    {
        $user     = User::factory()->create();
        $document = $this->makeDocument($user);
        $service  = $this->makeService();

        $service->join($document, $user, 'view');
        $service->leave($document, $user);

        $this->assertSame(0, DocumentSession::where('document_id', $document->id)
            ->where('user_id', $user->id)
            ->count());
    }

    public function test_leave_is_idempotent_when_no_session(): void
    {
        $user     = User::factory()->create();
        $document = $this->makeDocument($user);
        $service  = $this->makeService();

        // No exception thrown when no session exists
        $this->expectNotToPerformAssertions();
        $service->leave($document, $user);
    }

    // ─── DocumentPresenceService::getActiveSessions() ────────────────────────

    public function test_get_active_sessions_excludes_stale_sessions(): void
    {
        $user     = User::factory()->create();
        $document = $this->makeDocument($user);
        $service  = $this->makeService();

        // Create a stale session (last_seen_at > TTL ago)
        DocumentSession::create([
            'document_id'  => $document->id,
            'user_id'      => $user->id,
            'mode'         => 'view',
            'last_seen_at' => now()->subMinutes(DocumentPresenceService::TTL_MINUTES + 1),
            'joined_at'    => now()->subMinutes(10),
        ]);

        $sessions = $service->getActiveSessions($document);

        $this->assertCount(0, $sessions);
    }

    public function test_get_active_sessions_includes_fresh_sessions(): void
    {
        $userA    = User::factory()->create();
        $userB    = User::factory()->create();
        $document = $this->makeDocument($userA);
        $service  = $this->makeService();

        $service->join($document, $userA, 'edit');
        $service->join($document, $userB, 'view');

        $sessions = $service->getActiveSessions($document);

        $this->assertCount(2, $sessions);
    }

    public function test_get_active_editors_returns_only_edit_mode_sessions(): void
    {
        $userA    = User::factory()->create();
        $userB    = User::factory()->create();
        $document = $this->makeDocument($userA);
        $service  = $this->makeService();

        $service->join($document, $userA, 'edit');
        $service->join($document, $userB, 'view');

        $editors = $service->getActiveEditors($document);
        $viewers = $service->getActiveViewers($document);

        $this->assertCount(1, $editors);
        $this->assertEquals($userA->id, $editors->first()->user_id);
        $this->assertCount(1, $viewers);
        $this->assertEquals($userB->id, $viewers->first()->user_id);
    }

    // ─── DocumentPresenceService::cleanStaleSessions() ───────────────────────

    public function test_clean_stale_sessions_deletes_expired_rows(): void
    {
        $user     = User::factory()->create();
        $document = $this->makeDocument($user);
        $service  = $this->makeService();

        // 2 stale + 1 fresh
        DocumentSession::create([
            'document_id'  => $document->id,
            'user_id'      => $user->id,
            'mode'         => 'view',
            'last_seen_at' => now()->subMinutes(DocumentPresenceService::TTL_MINUTES + 10),
            'joined_at'    => now()->subMinutes(20),
        ]);

        $userB = User::factory()->create();
        DocumentSession::create([
            'document_id'  => $document->id,
            'user_id'      => $userB->id,
            'mode'         => 'edit',
            'last_seen_at' => now()->subMinutes(DocumentPresenceService::TTL_MINUTES + 5),
            'joined_at'    => now()->subMinutes(15),
        ]);

        $userC = User::factory()->create();
        DocumentSession::create([
            'document_id'  => $document->id,
            'user_id'      => $userC->id,
            'mode'         => 'view',
            'last_seen_at' => now()->subMinutes(1),
            'joined_at'    => now()->subMinutes(1),
        ]);

        $deleted = $service->cleanStaleSessions();

        $this->assertSame(2, $deleted);
        $this->assertSame(1, DocumentSession::count());
        $this->assertEquals($userC->id, DocumentSession::first()->user_id);
    }

    // ─── Document::activeSessions() relation ─────────────────────────────────

    public function test_document_active_sessions_relation_scope(): void
    {
        $user     = User::factory()->create();
        $document = $this->makeDocument($user);

        // Fresh session
        DocumentSession::create([
            'document_id'  => $document->id,
            'user_id'      => $user->id,
            'mode'         => 'view',
            'last_seen_at' => now()->subMinutes(2),
            'joined_at'    => now()->subMinutes(2),
        ]);

        $userB = User::factory()->create();
        // Stale session
        DocumentSession::create([
            'document_id'  => $document->id,
            'user_id'      => $userB->id,
            'mode'         => 'view',
            'last_seen_at' => now()->subMinutes(10),
            'joined_at'    => now()->subMinutes(10),
        ]);

        $active = $document->activeSessions()->get();

        $this->assertCount(1, $active);
        $this->assertEquals($user->id, $active->first()->user_id);
    }

    // ─── Presence routes ─────────────────────────────────────────────────────

    public function test_heartbeat_route_requires_authentication(): void
    {
        $user     = User::factory()->create();
        $document = $this->makeDocument($user);

        $response = $this->postJson(
            route('documents.presence.heartbeat', $document->id)
        );

        $response->assertStatus(401);
    }

    public function test_heartbeat_route_returns_ok_for_authenticated_user(): void
    {
        $user     = User::factory()->create();
        $document = $this->makeDocument($user);
        $service  = $this->makeService();

        // Ensure a session exists first
        $service->join($document, $user, 'view');

        $response = $this->actingAs($user)->postJson(
            route('documents.presence.heartbeat', $document->id)
        );

        $response->assertOk();
        $response->assertJsonFragment(['ok' => true]);
        $response->assertJsonStructure(['ok', 'sessions']);
    }

    public function test_leave_route_requires_authentication(): void
    {
        $user     = User::factory()->create();
        $document = $this->makeDocument($user);

        $response = $this->postJson(
            route('documents.presence.leave', $document->id)
        );

        $response->assertStatus(401);
    }

    public function test_leave_route_removes_session(): void
    {
        $user     = User::factory()->create();
        $document = $this->makeDocument($user);
        $service  = $this->makeService();

        $service->join($document, $user, 'view');

        $response = $this->actingAs($user)->postJson(
            route('documents.presence.leave', $document->id)
        );

        $response->assertOk();
        $this->assertSame(0, DocumentSession::where('document_id', $document->id)
            ->where('user_id', $user->id)
            ->count());
    }

    // ─── DocumentEditedNotification ──────────────────────────────────────────

    public function test_document_edited_notification_via_database(): void
    {
        Notification::fake();

        $editor   = User::factory()->create(['name' => 'Alice']);
        $viewer   = User::factory()->create();
        $document = $this->makeDocument($editor);

        $viewer->notify(new DocumentEditedNotification($document, $editor));

        Notification::assertSentTo(
            $viewer,
            DocumentEditedNotification::class,
            function (DocumentEditedNotification $notification) use ($document, $editor, $viewer): bool {
                $data = $notification->toArray($viewer);

                return $data['document_id'] === $document->id
                    && $data['edited_by'] === $editor->id
                    && str_contains($data['body'], 'Alice')
                    && str_contains($data['body'], $document->titre);
            }
        );
    }

    public function test_document_edited_notification_channels(): void
    {
        $editor   = User::factory()->create();
        $viewer   = User::factory()->create();
        $document = $this->makeDocument($editor);

        $notification = new DocumentEditedNotification($document, $editor);

        $this->assertContains('database', $notification->via($viewer));
    }
}
