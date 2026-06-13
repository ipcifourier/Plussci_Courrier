<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentAccessRule;
use App\Models\DocumentShare;
use App\Models\User;
use App\Notifications\DocumentSharedNotification;
use App\Services\DocumentShareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DocumentShareTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ────────────────────────────────────────────────────────────────

    private function makeDocument(User $user): Document
    {
        static $seq = 0;
        $seq++;

        return Document::query()->create([
            'reference_doc'         => 'DOC-SH-' . $seq,
            'titre'                 => 'Document partage ' . $seq,
            'type_document'         => 'Note',
            'etat_cycle_vie'        => 'Valide',
            'auteur_id'             => $user->id,
            'confidentiality_level' => 'Standard',
        ]);
    }

    // ─── Model helpers ──────────────────────────────────────────────────────────

    public function test_share_is_valid_when_not_expired_not_revoked(): void
    {
        $user     = User::factory()->create();
        $document = $this->makeDocument($user);

        $share = DocumentShare::create([
            'document_id'  => $document->id,
            'shared_by_id' => $user->id,
            'type'         => 'external',
            'token'        => DocumentShare::generateToken(),
            'can_view'     => true,
            'expires_at'   => Carbon::now()->addDays(7),
        ]);

        $this->assertTrue($share->isValid());
        $this->assertFalse($share->isExpired());
        $this->assertFalse($share->isRevoked());
    }

    public function test_share_is_expired_when_expires_at_in_past(): void
    {
        $user     = User::factory()->create();
        $document = $this->makeDocument($user);

        $share = DocumentShare::create([
            'document_id'  => $document->id,
            'shared_by_id' => $user->id,
            'type'         => 'external',
            'token'        => DocumentShare::generateToken(),
            'can_view'     => true,
            'expires_at'   => Carbon::now()->subHour(),
        ]);

        $this->assertTrue($share->isExpired());
        $this->assertFalse($share->isValid());
    }

    public function test_share_is_revoked_after_revoke(): void
    {
        $user     = User::factory()->create();
        $document = $this->makeDocument($user);

        $share = DocumentShare::create([
            'document_id'  => $document->id,
            'shared_by_id' => $user->id,
            'type'         => 'internal',
            'can_view'     => true,
        ]);

        app(DocumentShareService::class)->revoke($share);

        $this->assertTrue($share->fresh()->isRevoked());
        $this->assertFalse($share->fresh()->isValid());
    }

    public function test_share_url_is_null_for_internal_share(): void
    {
        $user     = User::factory()->create();
        $document = $this->makeDocument($user);

        $share = DocumentShare::create([
            'document_id'  => $document->id,
            'shared_by_id' => $user->id,
            'type'         => 'internal',
            'can_view'     => true,
        ]);

        $this->assertNull($share->shareUrl());
    }

    public function test_share_url_is_set_for_external_share(): void
    {
        $user     = User::factory()->create();
        $document = $this->makeDocument($user);

        $token = DocumentShare::generateToken();

        $share = DocumentShare::create([
            'document_id'  => $document->id,
            'shared_by_id' => $user->id,
            'type'         => 'external',
            'token'        => $token,
            'can_view'     => true,
        ]);

        $this->assertStringContainsString($token, $share->shareUrl());
    }

    public function test_record_access_increments_count(): void
    {
        $user     = User::factory()->create();
        $document = $this->makeDocument($user);

        $share = DocumentShare::create([
            'document_id'  => $document->id,
            'shared_by_id' => $user->id,
            'type'         => 'external',
            'token'        => DocumentShare::generateToken(),
            'can_view'     => true,
        ]);

        $this->assertEquals(0, $share->access_count);

        $share->recordAccess();
        $share->recordAccess();

        $this->assertEquals(2, $share->fresh()->access_count);
        $this->assertNotNull($share->fresh()->last_accessed_at);
    }

    // ─── Service: partage interne ────────────────────────────────────────────────

    public function test_share_with_user_creates_share_and_notifies(): void
    {
        Notification::fake();

        $sharedBy  = User::factory()->create();
        $recipient = User::factory()->create();
        $document  = $this->makeDocument($sharedBy);

        $share = app(DocumentShareService::class)->shareWithUser(
            document:    $document,
            recipient:   $recipient,
            sharedBy:    $sharedBy,
            canDownload: true,
        );

        $this->assertDatabaseHas('document_shares', [
            'document_id'       => $document->id,
            'shared_by_id'      => $sharedBy->id,
            'recipient_user_id' => $recipient->id,
            'type'              => 'internal',
            'can_view'          => true,
            'can_download'      => true,
        ]);

        Notification::assertSentTo($recipient, DocumentSharedNotification::class);
    }

    public function test_share_with_user_creates_access_rule(): void
    {
        Notification::fake();

        $sharedBy  = User::factory()->create();
        $recipient = User::factory()->create();
        $document  = $this->makeDocument($sharedBy);

        app(DocumentShareService::class)->shareWithUser($document, $recipient, $sharedBy);

        $this->assertDatabaseHas('document_access_rules', [
            'document_id' => $document->id,
            'user_id'     => $recipient->id,
            'can_view'    => true,
        ]);
    }

    public function test_share_with_user_respects_expiry(): void
    {
        Notification::fake();

        $sharedBy  = User::factory()->create();
        $recipient = User::factory()->create();
        $document  = $this->makeDocument($sharedBy);
        $expiresAt = Carbon::now()->addDays(14);

        $share = app(DocumentShareService::class)->shareWithUser(
            document:   $document,
            recipient:  $recipient,
            sharedBy:   $sharedBy,
            expiresAt:  $expiresAt,
        );

        $this->assertEquals($expiresAt->toDateString(), $share->expires_at->toDateString());
        $this->assertTrue($share->isValid());
    }

    // ─── Service: partage externe ────────────────────────────────────────────────

    public function test_share_with_email_creates_token_and_sends_mail(): void
    {
        Notification::fake();

        $sharedBy = User::factory()->create();
        $document = $this->makeDocument($sharedBy);
        $email    = 'externe@example.com';

        $share = app(DocumentShareService::class)->shareWithEmail(
            document:       $document,
            recipientEmail: $email,
            sharedBy:       $sharedBy,
            expiresAt:      Carbon::now()->addDays(3),
        );

        $this->assertDatabaseHas('document_shares', [
            'document_id'     => $document->id,
            'recipient_email' => $email,
            'type'            => 'external',
        ]);

        $this->assertNotNull($share->token);
        $this->assertTrue($share->isValid());

        Notification::assertSentOnDemand(DocumentSharedNotification::class);
    }

    public function test_share_with_email_no_access_rule_created(): void
    {
        Notification::fake();

        $sharedBy = User::factory()->create();
        $document = $this->makeDocument($sharedBy);

        app(DocumentShareService::class)->shareWithEmail(
            document:       $document,
            recipientEmail: 'ext@test.com',
            sharedBy:       $sharedBy,
            expiresAt:      Carbon::now()->addDay(),
        );

        // External shares don't create DocumentAccessRules (token-based)
        $this->assertDatabaseMissing('document_access_rules', [
            'document_id' => $document->id,
        ]);
    }

    // ─── Public route ────────────────────────────────────────────────────────────

    public function test_public_share_route_renders_for_valid_token(): void
    {
        Notification::fake();

        $sharedBy = User::factory()->create();
        $document = $this->makeDocument($sharedBy);

        $share = app(DocumentShareService::class)->shareWithEmail(
            document:       $document,
            recipientEmail: 'guest@example.com',
            sharedBy:       $sharedBy,
            expiresAt:      Carbon::now()->addDays(3),
        );

        $response = $this->get(route('share.show', $share->token));

        $response->assertOk();
        $response->assertSee($document->titre, false);
    }

    public function test_public_share_route_returns_410_for_expired_token(): void
    {
        Notification::fake();

        $sharedBy = User::factory()->create();
        $document = $this->makeDocument($sharedBy);

        $share = app(DocumentShareService::class)->shareWithEmail(
            document:       $document,
            recipientEmail: 'guest@example.com',
            sharedBy:       $sharedBy,
            expiresAt:      Carbon::now()->subHour(),   // already expired
        );

        $response = $this->get(route('share.show', $share->token));

        $response->assertStatus(410);
    }

    public function test_public_share_route_returns_410_for_revoked_share(): void
    {
        Notification::fake();

        $sharedBy = User::factory()->create();
        $document = $this->makeDocument($sharedBy);

        $share = app(DocumentShareService::class)->shareWithEmail(
            document:       $document,
            recipientEmail: 'guest@example.com',
            sharedBy:       $sharedBy,
            expiresAt:      Carbon::now()->addDays(3),
        );

        app(DocumentShareService::class)->revoke($share);

        $response = $this->get(route('share.show', $share->fresh()->token));

        $response->assertStatus(410);
    }

    public function test_public_share_route_returns_404_for_unknown_token(): void
    {
        $response = $this->get(route('share.show', 'not-a-real-token'));

        $response->assertNotFound();
    }

    public function test_public_share_increments_access_count_on_visit(): void
    {
        Notification::fake();

        $sharedBy = User::factory()->create();
        $document = $this->makeDocument($sharedBy);

        $share = app(DocumentShareService::class)->shareWithEmail(
            document:       $document,
            recipientEmail: 'visitor@example.com',
            sharedBy:       $sharedBy,
            expiresAt:      Carbon::now()->addDays(1),
        );

        $this->get(route('share.show', $share->token));
        $this->get(route('share.show', $share->token));

        $this->assertEquals(2, $share->fresh()->access_count);
    }

    // ─── generate_token uniqueness ───────────────────────────────────────────────

    public function test_generate_token_returns_64_char_string(): void
    {
        $token = DocumentShare::generateToken();

        $this->assertEquals(64, strlen($token));
    }

    public function test_document_has_shares_relation(): void
    {
        Notification::fake();

        $sharedBy  = User::factory()->create();
        $recipient = User::factory()->create();
        $document  = $this->makeDocument($sharedBy);

        app(DocumentShareService::class)->shareWithUser($document, $recipient, $sharedBy);

        $this->assertCount(1, $document->shares);
    }
}
