<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSession extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'joined_at'    => 'datetime',
    ];

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isEditor(): bool
    {
        return $this->mode === 'edit';
    }

    public function isViewer(): bool
    {
        return $this->mode === 'view';
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
