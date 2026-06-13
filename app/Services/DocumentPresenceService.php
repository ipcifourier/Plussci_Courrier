<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentSession;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Tracks who is currently viewing or editing a document.
 *
 * Session TTL: 5 minutes without a heartbeat → considered stale.
 */
class DocumentPresenceService
{
    public const TTL_MINUTES = 5;

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Register or refresh a user's session on a document.
     * If a session already exists for (document, user), updates mode + last_seen_at.
     */
    public function join(Document $document, User $user, string $mode = 'view'): DocumentSession
    {
        return DocumentSession::updateOrCreate(
            ['document_id' => $document->id, 'user_id' => $user->id],
            [
                'mode'         => $mode,
                'last_seen_at' => now(),
                'joined_at'    => now(),  // updateOrCreate won't overwrite if column already exists via firstOrCreate
            ]
        );
    }

    /**
     * Update last_seen_at for a user's session (keep-alive call).
     */
    public function heartbeat(Document $document, User $user): void
    {
        DocumentSession::where('document_id', $document->id)
            ->where('user_id', $user->id)
            ->update(['last_seen_at' => now()]);
    }

    /**
     * Remove a user's session (they left the page).
     */
    public function leave(Document $document, User $user): void
    {
        DocumentSession::where('document_id', $document->id)
            ->where('user_id', $user->id)
            ->delete();
    }

    /**
     * Return all non-stale sessions on a document, with user eager-loaded.
     */
    public function getActiveSessions(Document $document): Collection
    {
        return DocumentSession::where('document_id', $document->id)
            ->where('last_seen_at', '>=', now()->subMinutes(self::TTL_MINUTES))
            ->with('user')
            ->orderBy('joined_at')
            ->get();
    }

    /**
     * Return only active sessions in 'edit' mode (should normally be 0 or 1).
     */
    public function getActiveEditors(Document $document): Collection
    {
        return $this->getActiveSessions($document)->where('mode', 'edit');
    }

    /**
     * Return active sessions in 'view' mode.
     */
    public function getActiveViewers(Document $document): Collection
    {
        return $this->getActiveSessions($document)->where('mode', 'view');
    }

    /**
     * Remove all sessions older than TTL (cleanup command or scheduled task).
     */
    public function cleanStaleSessions(): int
    {
        return DocumentSession::where('last_seen_at', '<', now()->subMinutes(self::TTL_MINUTES))
            ->delete();
    }
}
