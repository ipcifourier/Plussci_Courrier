<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSignature extends Model
{
    protected $guarded = [];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function signataire()
    {
        return $this->belongsTo(User::class, 'signataire_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isSigned(): bool
    {
        return $this->status === 'signed';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /** Label affiché pour le rôle associé au statut. */
    public function statusLabel(): string
    {
        return match ($this->status) {
            'signed'   => '✔ ' . $this->role_signature,
            'rejected' => '✖ Rejeté',
            default    => '⏳ En attente',
        };
    }
}
