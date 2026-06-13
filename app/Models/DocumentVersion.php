<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentVersion extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The Media record attached to this version.
     */
    public function media()
    {
        return $this->belongsTo(\Spatie\MediaLibrary\MediaCollections\Models\Media::class);
    }

    /**
     * Returns true if OCR has extracted usable text for this version.
     */
    public function hasOcrText(): bool
    {
        return $this->ocr_status === 'completed' && ! empty($this->ocr_text);
    }

    /**
     * Human-readable OCR status label.
     */
    public function ocrStatusLabel(): string
    {
        return match ($this->ocr_status) {
            'pending'     => 'En attente',
            'processing'  => 'En cours…',
            'completed'   => 'Indexé',
            'failed'      => 'Échec',
            'unavailable' => 'Non disponible',
            default       => $this->ocr_status ?? 'Inconnu',
        };
    }
}
