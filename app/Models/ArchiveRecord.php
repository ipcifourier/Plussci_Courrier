<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchiveRecord extends Model
{
    protected $guarded = [];

    protected $casts = [
        'archived_at'          => 'datetime',
        'retention_expires_at' => 'date',
        'verified_at'          => 'datetime',
        'manifest_json'        => 'array',
        'retention_years'      => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function archivedBy()
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Is this archive record past its legal retention expiry?
     */
    public function isExpired(): bool
    {
        return $this->retention_expires_at !== null
            && $this->retention_expires_at->isPast();
    }

    /**
     * Integrity status label for display.
     */
    public function integrityLabel(): string
    {
        return match ($this->integrity_status) {
            'verified'  => 'Intégre',
            'corrupted' => 'Corrompu',
            default     => 'En attente',
        };
    }

    /**
     * Integrity status badge color.
     */
    public function integrityColor(): string
    {
        return match ($this->integrity_status) {
            'verified'  => 'success',
            'corrupted' => 'danger',
            default     => 'warning',
        };
    }
}
