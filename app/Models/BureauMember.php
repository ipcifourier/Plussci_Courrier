<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BureauMember extends Model
{
    protected $fillable = [
        'nom',
        'prenom',
        'fonction',
        'email',
        'telephone',
        'photo',
        'date_entree',
        'statut',
        'gtt_id',
        'structure_id',
    ];

    protected $casts = [
        'date_entree' => 'date',
        'statut'      => 'boolean',
    ];

    public function gtt(): BelongsTo
    {
        return $this->belongsTo(Gtt::class);
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }
}
