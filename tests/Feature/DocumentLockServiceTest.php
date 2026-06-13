<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use App\Services\DocumentLockService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentLockServiceTest extends TestCase
{
    use RefreshDatabase;

    private DocumentLockService $service;
    private User $user1;
    private User $user2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DocumentLockService::class);
        $this->user1   = User::factory()->create();
        $this->user2   = User::factory()->create();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private static int $seq = 0;

    private function makeDocument(array $overrides = []): Document
    {
        self::$seq++;
        return Document::create(array_merge([
            'reference_doc'         => 'LCK-' . self::$seq,
            'titre'                 => 'Lock test doc ' . self::$seq,
            'type_document'         => 'Document',
            'etat_cycle_vie'        => 'Brouillon',
            'auteur_id'             => $this->user1->id,
            'confidentiality_level' => 'Standard',
        ], $overrides));
    }

    // ─── acquire() ───────────────────────────────────────────────────────────

    public function test_acquire_locks_a_free_document(): void
    {
        $doc = $this->makeDocument();

        $result = $this->service->acquire($doc, $this->user1);

        $this->assertTrue($result);
        $doc->refresh();
        $this->assertEquals($this->user1->id, $doc->locked_by);
        $this->assertNotNull($doc->locked_at);
    }

    public function test_acquire_returns_true_for_the_same_user_re_acquiring(): void
    {
        $doc = $this->makeDocument();
        $this->service->acquire($doc, $this->user1);

        // Same user re-acquires (renew)
        $result = $this->service->acquire($doc, $this->user1);

        $this->assertTrue($result);
    }

    public function test_acquire_returns_false_when_locked_by_another_user(): void
    {
        $doc = $this->makeDocument();
        $this->service->acquire($doc, $this->user1);

        $result = $this->service->acquire($doc, $this->user2);

        $this->assertFalse($result);
    }

    public function test_acquire_succeeds_after_lock_has_expired(): void
    {
        $doc = $this->makeDocument();

        // Simulate an expired lock owned by user1
        $doc->update([
            'locked_by' => $this->user1->id,
            'locked_at' => Carbon::now()->subMinutes(DocumentLockService::LOCK_TTL_MINUTES + 1),
        ]);

        // user2 should be able to acquire
        $result = $this->service->acquire($doc, $this->user2);

        $this->assertTrue($result);
        $doc->refresh();
        $this->assertEquals($this->user2->id, $doc->locked_by);
    }

    // ─── release() ───────────────────────────────────────────────────────────

    public function test_release_clears_the_lock_for_the_owning_user(): void
    {
        $doc = $this->makeDocument();
        $this->service->acquire($doc, $this->user1);

        $this->service->release($doc, $this->user1);

        $doc->refresh();
        $this->assertNull($doc->locked_by);
        $this->assertNull($doc->locked_at);
    }

    public function test_release_does_nothing_when_called_by_non_owner(): void
    {
        $doc = $this->makeDocument();
        $this->service->acquire($doc, $this->user1);

        $this->service->release($doc, $this->user2);

        $doc->refresh();
        // user1's lock should still be intact
        $this->assertEquals($this->user1->id, $doc->locked_by);
    }

    // ─── forceRelease() ──────────────────────────────────────────────────────

    public function test_force_release_clears_any_lock(): void
    {
        $doc = $this->makeDocument();
        $this->service->acquire($doc, $this->user1);

        $this->service->forceRelease($doc);

        $doc->refresh();
        $this->assertNull($doc->locked_by);
    }

    // ─── isLockedByOther() ────────────────────────────────────────────────────

    public function test_is_locked_by_other_returns_false_for_free_document(): void
    {
        $doc = $this->makeDocument();

        $this->assertFalse($this->service->isLockedByOther($doc, $this->user1));
    }

    public function test_is_locked_by_other_returns_true_when_locked_by_different_user(): void
    {
        $doc = $this->makeDocument();
        $this->service->acquire($doc, $this->user1);

        $this->assertTrue($this->service->isLockedByOther($doc, $this->user2));
    }

    // ─── getLockHolder() ─────────────────────────────────────────────────────

    public function test_get_lock_holder_returns_the_locking_user(): void
    {
        $doc = $this->makeDocument();
        $this->service->acquire($doc, $this->user1);

        $holder = $this->service->getLockHolder($doc);

        $this->assertNotNull($holder);
        $this->assertEquals($this->user1->id, $holder->id);
    }

    public function test_get_lock_holder_returns_null_for_free_document(): void
    {
        $doc = $this->makeDocument();

        $this->assertNull($this->service->getLockHolder($doc));
    }

    // ─── lockExpiresAt() ─────────────────────────────────────────────────────

    public function test_lock_expires_at_returns_correct_timestamp(): void
    {
        $doc = $this->makeDocument();
        $before = Carbon::now();
        $this->service->acquire($doc, $this->user1);
        $after = Carbon::now();

        $expiresAt = $this->service->lockExpiresAt($doc);

        $this->assertNotNull($expiresAt);
        // Add a 2-second tolerance to account for DB timestamp precision loss (no microseconds)
        $expectedMin = $before->copy()->addMinutes(DocumentLockService::LOCK_TTL_MINUTES)->subSeconds(2);
        $expectedMax = $after->copy()->addMinutes(DocumentLockService::LOCK_TTL_MINUTES)->addSeconds(2);
        $this->assertTrue(
            $expiresAt->between($expectedMin, $expectedMax),
            "Lock expiry {$expiresAt} not between {$expectedMin} and {$expectedMax}"
        );
    }
}
