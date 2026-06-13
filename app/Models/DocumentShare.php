<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DocumentShare extends Model
{
    protected $guarded = [];

    protected $casts = [
        'can_view'          => 'boolean',
        'can_download'      => 'boolean',
        'can_comment'       => 'boolean',
        'can_edit'          => 'boolean',
        'expires_at'        => 'datetime',
        'revoked_at'        => 'datetime',
        'last_accessed_at'  => 'datetime',
        'access_count'      => 'integer',
    ];

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->isRevoked();
    }

    public function isInternal(): bool
    {
        return $this->type === 'internal';
    }

    public function isExternal(): bool
    {
        return $this->type === 'external';
    }

    /** Enregistre un accès (incrémente compteur + timestamp). */
    public function recordAccess(): void
    {
        $this->updateQuietly([
            'access_count'      => $this->access_count + 1,
            'last_accessed_at'  => now(),
        ]);
    }

    /** Génère (ou retourne) un token unique. */
    public static function generateToken(): string
    {
        do {
            $token = Str::random(64);
        } while (static::where('token', $token)->exists());

        return $token;
    }

    /** Retourne l'URL d'accès public pour les partages externes. */
    public function shareUrl(): ?string
    {
        if ($this->type !== 'external' || ! $this->token) {
            return null;
        }

        return route('share.show', $this->token);
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function sharedBy()
    {
        return $this->belongsTo(User::class, 'shared_by_id');
    }

    public function recipientUser()
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }
}
