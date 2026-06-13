<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use Carbon\Carbon;

/**
 * Pessimistic locking service for documents.
 *
 * A "lock" means a User has opened the Edit page and claimed exclusive write access.
 * Locks auto-expire after LOCK_TTL_MINUTES of inactivity.
 */
class DocumentLockService
{
    /** Lock expiry duration in minutes. */
    public const LOCK_TTL_MINUTES = 30;

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Try to acquire the lock for $user on $document.
     *
     * Returns true  — lock acquired (was free, expired, or already owned by $user).
     * Returns false — locked by someone else and still active.
     */
    public function acquire(Document $document, User $user): bool
    {
        $document->refresh();

        // If not locked, or lock expired, or already owned by this user → grant
        if ($this->isFreeOrExpired($document) || $document->locked_by === $user->id) {
            $document->update([
                'locked_by' => $user->id,
                'locked_at' => Carbon::now(),
            ]);

            return true;
        }

        return false;
    }

    /**
     * Renew the lock expiry for $user (heartbeat).
     * Does nothing if the lock is not owned by $user.
     */
    public function renew(Document $document, User $user): void
    {
        $document->refresh();

        if ($document->locked_by === $user->id) {
            $document->update(['locked_at' => Carbon::now()]);
        }
    }

    /**
     * Release the lock if currently owned by $user.
     */
    public function release(Document $document, User $user): void
    {
        $document->refresh();

        if ($document->locked_by === $user->id) {
            $document->update(['locked_by' => null, 'locked_at' => null]);
        }
    }

    /**
     * Force-release any lock on $document (Super Admin / admin use).
     */
    public function forceRelease(Document $document): void
    {
        $document->update(['locked_by' => null, 'locked_at' => null]);
    }

    /**
     * Returns true if locked by someone OTHER than $user and the lock has not expired.
     */
    public function isLockedByOther(Document $document, User $user): bool
    {
        $document->refresh();

        if ($this->isFreeOrExpired($document)) {
            return false;
        }

        return $document->locked_by !== $user->id;
    }

    /**
     * Returns the User who currently holds the lock, or null if free / expired.
     */
    public function getLockHolder(Document $document): ?User
    {
        $document->refresh();

        if ($this->isFreeOrExpired($document)) {
            return null;
        }

        return $document->lockedBy;
    }

    /**
     * Returns the Carbon timestamp when the current lock will expire, or null if not locked.
     */
    public function lockExpiresAt(Document $document): ?Carbon
    {
        $document->refresh();

        if ($this->isFreeOrExpired($document)) {
            return null;
        }

        return Carbon::parse($document->locked_at)->addMinutes(self::LOCK_TTL_MINUTES);
    }

    // ── Internal helpers ──────────────────────────────────────────────────────

    private function isFreeOrExpired(Document $document): bool
    {
        if ($document->locked_by === null || $document->locked_at === null) {
            return true;
        }

        $expiresAt = Carbon::parse($document->locked_at)->addMinutes(self::LOCK_TTL_MINUTES);

        return Carbon::now()->gte($expiresAt);
    }
}
