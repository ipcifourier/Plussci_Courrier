<?php

namespace App\Models;

use App\Notifications\ImputationAssignedNotification;
use Illuminate\Database\Eloquent\Model;

class Imputation extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::created(function (self $imputation): void {
            $imputation->loadMissing(['destinataire', 'expediteur', 'courrier']);

            if (! $imputation->destinataire) {
                return;
            }

            $imputation->destinataire->notify(new ImputationAssignedNotification($imputation));
        });
    }

    protected $casts = [
        'date_imputation'   => 'datetime',
        'delai_traitement'  => 'date',
        'relance_envoyee_at' => 'datetime',
    ];

    /** True si le délai de traitement est dépassé et l'imputation non traitée. */
    public function isOverdue(): bool
    {
        return $this->delai_traitement !== null
            && $this->delai_traitement->isPast()
            && $this->statut_traitement !== 'Traité';
    }

    /** True si une relance a déjà été envoyée aujourd'hui. */
    public function relanceEnvoyeeAujourdhui(): bool
    {
        return $this->relance_envoyee_at !== null
            && $this->relance_envoyee_at->isToday();
    }

    public function courrier()
    {
        return $this->belongsTo(Courrier::class);
    }

    public function expediteur()
    {
        return $this->belongsTo(User::class, 'expediteur_id');
    }

    public function destinataire()
    {
        return $this->belongsTo(User::class, 'destinataire_id');
    }
    
}
