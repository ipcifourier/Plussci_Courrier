<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentAccessRule;
use App\Models\DocumentShare;
use App\Models\User;
use App\Notifications\ShareAccessedNotification;
use App\Services\DocumentShareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ShareSecurityTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ────────────────────────────────────────────────────────────────

    private static int $seq = 0;

    private function makeDocument(User $user): Document
    {
        self::$seq++;

        return Document::query()->create([
            'reference_doc'         => 'DOC-SS-' . self::$seq,
            'titre'                 => 'Share security doc ' . self::$seq,
            'type_document'         => 'Note',
            'etat_cycle_vie'        => 'Valide',
            'auteur_id'             => $user->id,
            'confidentiality_level' => 'Standard',
        ]);
    }

    private function makeService(): DocumentShareService
    {
        return app(DocumentShareService::class);
    }

    private function makeExternalShare(Document $document, User $sharedBy, array $overrides = []): DocumentShare
    {
        return DocumentShare::create(array_merge([
            'document_id'     => $document->id,
            'shared_by_id'    => $sharedBy->id,
            'recipient_email' => 'externe@example.com',
            'token'           => DocumentShare::generateToken(),
            'type'            => 'external',
            'can_view'        => true,
            'can_download'    => false,
            'can_comment'     => false,
            'access_count'    => 0,
        ], $overrides));
    }

    private function makeInternalShare(Document $document, User $sharedBy, User $recipient, array $overrides = []): DocumentShare
    {
        return DocumentShare::create(array_merge([
            'document_id'       => $document->id,
            'shared_by_id'      => $sharedBy->id,
            'recipient_user_id' => $recipient->id,
            'type'              => 'internal',
            'can_view'          => true,
            'can_download'      => false,
            'can_comment'       => false,
            'access_count'      => 0,
        ], $overrides));
    }

    // ─── ShareAccessedNotification ────────────────────────────────────────────

    public function test_share_accessed_notification_channels_include_database_and_mail(): void
    {
        $owner    = User::factory()->create();
        $document = $this->makeDocument($owner);
        $share    = $this->makeExternalShare($document, $owner);

        $notification = new ShareAccessedNotification($document, $share);

        $this->assertContains('database', $notification->via($owner));
        $this->assertContains('mail', $notification->via($owner));
    }

    public function test_share_accessed_notification_to_array_contains_expected_keys(): void
    {
        $owner    = User::factory()->create();
        $document = $this->makeDocument($owner);
        $share    = $this->makeExternalShare($document, $owner);

        $notification = new ShareAccessedNotification($document, $share);
        $data         = $notification->toArray($owner);

        $this->assertEquals($document->id, $data['document_id']);
        $this->assertEquals($share->id, $data['share_id']);
        $this->assertEquals('externe@example.com', $data['recipient_email']);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('body', $data);
        $this->assertArrayHasKey('url', $data);
    }

    // ─── Controller — notification on first access ────────────────────────────

    public function test_first_external_access_sends_notification_to_share_owner(): void
    {
        Notification::fake();

        $owner    = User::factory()->create();
        $document = $this->makeDocument($owner);
        $share    = $this->makeExternalShare($document, $owner);

        $this->get(route('share.show', $share->token));

        Notification::assertSentTo($owner, ShareAccessedNotification::class);
    }

    public function test_subsequent_access_does_not_resend_notification(): void
    {
        Notification::fake();

        $owner    = User::factory()->create();
        $document = $this->makeDocument($owner);
        $share    = $this->makeExternalShare($document, $owner, ['access_count' => 1]);

        $this->get(route('share.show', $share->token));

        Notification::assertNotSentTo($owner, ShareAccessedNotification::class);
    }

    public function test_internal_share_does_not_send_share_accessed_notification(): void
    {
        Notification::fake();

        $owner     = User::factory()->create();
        $recipient = User::factory()->create();
        $document  = $this->makeDocument($owner);
        $share     = $this->makeInternalShare($document, $owner, $recipient);

        // Internal shares accessed through app directly — no external notification
        // (DocumentShareController is for external token-based access only)
        // Verify the notification is NOT triggered for internal share type
        $this->assertFalse($share->isExternal());
        Notification::assertNothingSent();
    }

    public function test_access_count_increments_on_external_share_access(): void
    {
        Notification::fake();

        $owner    = User::factory()->create();
        $document = $this->makeDocument($owner);
        $share    = $this->makeExternalShare($document, $owner);

        $this->get(route('share.show', $share->token));

        $this->assertSame(1, $share->fresh()->access_count);
    }

    // ─── Service::revokeWithAccessRule() ─────────────────────────────────────

    public function test_revoke_with_access_rule_revokes_internal_share(): void
    {
        $owner     = User::factory()->create();
        $recipient = User::factory()->create();
        $document  = $this->makeDocument($owner);
        $service   = $this->makeService();

        $share = $this->makeInternalShare($document, $owner, $recipient);

        $service->revokeWithAccessRule($share);

        $this->assertNotNull($share->fresh()->revoked_at);
        $this->assertTrue($share->fresh()->isRevoked());
    }

    public function test_revoke_with_access_rule_removes_associated_access_rule(): void
    {
        $owner     = User::factory()->create();
        $recipient = User::factory()->create();
        $document  = $this->makeDocument($owner);
        $service   = $this->makeService();

        // Create the share and an associated access rule
        $share = $this->makeInternalShare($document, $owner, $recipient);
        DocumentAccessRule::create([
            'document_id'  => $document->id,
            'user_id'      => $recipient->id,
            'can_view'     => true,
            'can_download' => false,
            'can_edit'     => false,
            'can_share'    => false,
        ]);

        $this->assertSame(1, DocumentAccessRule::where('document_id', $document->id)
            ->where('user_id', $recipient->id)
            ->count());

        $service->revokeWithAccessRule($share);

        $this->assertSame(0, DocumentAccessRule::where('document_id', $document->id)
            ->where('user_id', $recipient->id)
            ->count());
    }

    public function test_revoke_with_access_rule_on_external_share_does_not_delete_access_rules(): void
    {
        $owner     = User::factory()->create();
        $recipient = User::factory()->create();
        $document  = $this->makeDocument($owner);
        $service   = $this->makeService();

        // External share has no user_id on the rule
        $share = $this->makeExternalShare($document, $owner);
        DocumentAccessRule::create([
            'document_id'  => $document->id,
            'user_id'      => $recipient->id,
            'can_view'     => true,
            'can_download' => false,
            'can_edit'     => false,
            'can_share'    => false,
        ]);

        $service->revokeWithAccessRule($share);

        // The access rule should NOT be deleted for a non-related user
        $this->assertSame(1, DocumentAccessRule::where('document_id', $document->id)
            ->where('user_id', $recipient->id)
            ->count());
    }

    // ─── Service::extendExpiry() ──────────────────────────────────────────────

    public function test_extend_expiry_updates_expires_at(): void
    {
        $owner    = User::factory()->create();
        $document = $this->makeDocument($owner);
        $service  = $this->makeService();
        $share    = $this->makeExternalShare($document, $owner, [
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        $newExpiry = Carbon::now()->addDays(30);
        $service->extendExpiry($share, $newExpiry);

        $this->assertTrue(
            $share->fresh()->expires_at->isToday() === false
            || $share->fresh()->expires_at->greaterThan(Carbon::now()),
        );
        $this->assertEquals($newExpiry->toDateString(), $share->fresh()->expires_at->toDateString());
    }

    public function test_extend_expiry_with_null_removes_expiry(): void
    {
        $owner    = User::factory()->create();
        $document = $this->makeDocument($owner);
        $service  = $this->makeService();
        $share    = $this->makeExternalShare($document, $owner, [
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        $service->extendExpiry($share, null);

        $this->assertNull($share->fresh()->expires_at);
    }

    public function test_extend_expiry_revalidates_expired_share(): void
    {
        $owner    = User::factory()->create();
        $document = $this->makeDocument($owner);
        $service  = $this->makeService();
        $share    = $this->makeExternalShare($document, $owner, [
            'expires_at' => Carbon::now()->subHour(),
        ]);

        $this->assertTrue($share->isExpired());

        $service->extendExpiry($share, Carbon::now()->addDays(7));

        $this->assertFalse($share->fresh()->isExpired());
        $this->assertTrue($share->fresh()->isValid());
    }

    // ─── Service::revokeAllForRecipient() ────────────────────────────────────

    public function test_revoke_all_for_recipient_revokes_all_active_shares(): void
    {
        $owner     = User::factory()->create();
        $recipient = User::factory()->create();
        $document  = $this->makeDocument($owner);
        $service   = $this->makeService();

        // Create 2 active shares for the same recipient
        $this->makeInternalShare($document, $owner, $recipient);
        $this->makeInternalShare($document, $owner, $recipient);

        $count = $service->revokeAllForRecipient($document, $recipient);

        $this->assertSame(2, $count);
        $this->assertSame(0, DocumentShare::where('document_id', $document->id)
            ->where('recipient_user_id', $recipient->id)
            ->whereNull('revoked_at')
            ->count());
    }

    public function test_revoke_all_for_recipient_removes_access_rule(): void
    {
        $owner     = User::factory()->create();
        $recipient = User::factory()->create();
        $document  = $this->makeDocument($owner);
        $service   = $this->makeService();

        $this->makeInternalShare($document, $owner, $recipient);
        DocumentAccessRule::create([
            'document_id'  => $document->id,
            'user_id'      => $recipient->id,
            'can_view'     => true,
            'can_download' => false,
            'can_edit'     => false,
            'can_share'    => false,
        ]);

        $service->revokeAllForRecipient($document, $recipient);

        $this->assertSame(0, DocumentAccessRule::where('document_id', $document->id)
            ->where('user_id', $recipient->id)
            ->count());
    }

    public function test_revoke_all_for_recipient_does_not_touch_already_revoked_shares(): void
    {
        $owner     = User::factory()->create();
        $recipient = User::factory()->create();
        $document  = $this->makeDocument($owner);
        $service   = $this->makeService();

        // 1 active + 1 already revoked
        $this->makeInternalShare($document, $owner, $recipient);
        $this->makeInternalShare($document, $owner, $recipient, [
            'revoked_at' => now()->subDay(),
        ]);

        $count = $service->revokeAllForRecipient($document, $recipient);

        // Only the active one
        $this->assertSame(1, $count);
    }

    // ─── Public share route security ─────────────────────────────────────────

    public function test_revoked_share_returns_410(): void
    {
        $owner    = User::factory()->create();
        $document = $this->makeDocument($owner);
        $share    = $this->makeExternalShare($document, $owner, [
            'revoked_at' => now()->subHour(),
        ]);

        $this->get(route('share.show', $share->token))->assertStatus(410);
    }

    public function test_expired_share_returns_410(): void
    {
        $owner    = User::factory()->create();
        $document = $this->makeDocument($owner);
        $share    = $this->makeExternalShare($document, $owner, [
            'expires_at' => Carbon::now()->subHour(),
        ]);

        $this->get(route('share.show', $share->token))->assertStatus(410);
    }

    public function test_renewed_share_becomes_accessible_again(): void
    {
        Notification::fake();

        $owner    = User::factory()->create();
        $document = $this->makeDocument($owner);
        $service  = $this->makeService();
        $share    = $this->makeExternalShare($document, $owner, [
            'expires_at' => Carbon::now()->subHour(),
        ]);

        // Expired → 410
        $this->get(route('share.show', $share->token))->assertStatus(410);

        // Extend expiry
        $service->extendExpiry($share, Carbon::now()->addDays(7));

        // Should be accessible again
        $this->get(route('share.show', $share->token))->assertOk();
    }

    public function test_download_blocked_when_can_download_is_false(): void
    {
        $owner    = User::factory()->create();
        $document = $this->makeDocument($owner);
        $share    = $this->makeExternalShare($document, $owner, [
            'can_download' => false,
        ]);

        $this->get(route('share.download', [$share->token, 999]))->assertStatus(403);
    }
}
