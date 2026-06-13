<?php

namespace App\Models;

use App\Notifications\CourrierApprovalDecisionNotification;
use App\Notifications\CourrierApprovalRequestedNotification;
use App\Notifications\CourrierSignedNotification;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Courrier extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = [];

    protected $attributes = [
        'canal'            => 'Physique',
        'scan_status'      => 'Non numérisé',
        'accuse_reception' => false,
    ];

    protected $casts = [
        'date_reception_envoi' => 'date',
        'requires_approval'    => 'boolean',
        'signed_at'            => 'datetime',
        'delai_reponse'        => 'date',
        'accuse_reception'     => 'boolean',
        'date_accuse'          => 'datetime',
        'date_numerisation'    => 'date',
        'collaboration_enabled' => 'boolean',
        'cloud_links'           => 'array',
    ];
    /** Sessions de co-édition (collaboration temps réel) */
    public function activeSessions()
    {
        return $this->hasMany(\App\Models\DocumentSession::class, 'courrier_id')
            ->where('last_seen_at', '>=', now()->subMinutes(5));
    }

    /** Liens cloud associés (O365, Google Docs, etc.) */
    public function getCloudLinks(): array
    {
        return $this->cloud_links ?? [];
    }

    public function correspondant()
    {
        return $this->belongsTo(Correspondant::class);
    }

    public function initiateur()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function imputations()
    {
        return $this->hasMany(Imputation::class);
    }

    /** C5 — Documents associés */
    public function documents()
    {
        return $this->hasMany(\App\Models\Document::class, 'courrier_id');
    }

    public function approvals()
    {
        return $this->hasMany(CourrierApproval::class);
    }

    public function signer()
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public function numerisePar()
    {
        return $this->belongsTo(User::class, 'numerise_par');
    }

    /** Retourne les mots-clés sous forme de tableau. */
    public function getMotsClesArrayAttribute(): array
    {
        if (blank($this->mots_cles)) {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $this->mots_cles)));
    }

    /** Indique si le courrier est en retard (délai de réponse dépassé). */
    public function isEnRetard(): bool
    {
        return $this->delai_reponse
            && $this->delai_reponse->isPast()
            && ! in_array($this->statut, ['Traité', 'Archivé'], true);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable')->latest();
    }

    public function tasks()
    {
        return $this->morphMany(Task::class, 'taskable')->latest();
    }

    public function canBeSigned(): bool
    {
        if ($this->type !== 'Sortant' || $this->signed_at) {
            return false;
        }

        if ($this->requires_approval && $this->approval_status !== 'approved') {
            return false;
        }

        return $this->statut === 'Traité';
    }

    public function notifyCurrentApprovers(): void
    {
        if (! $this->current_approval_level) {
            return;
        }

        $this->loadMissing('approvals.approver');

        $this->approvals
            ->where('level', $this->current_approval_level)
            ->where('status', 'pending')
            ->each(fn (CourrierApproval $approval) => $approval->approver?->notify(new CourrierApprovalRequestedNotification($this)));
    }

    public function notifyInitiatorDecision(string $decision, ?string $comment = null): void
    {
        $this->loadMissing('initiateur');

        $this->initiateur?->notify(new CourrierApprovalDecisionNotification($this, $decision, $comment));
    }

    public function notifyInitiatorSigned(User $signer): void
    {
        $this->loadMissing('initiateur');

        if (! $this->initiateur || $this->initiateur->id === $signer->id) {
            return;
        }

        $this->initiateur->notify(new CourrierSignedNotification($this, $signer));
    }

    //Enregistrement de la pièce jointe et les collections associées
    public function registerMediaCollections(): void
    {
        // Collection pour les pièces jointes du courrier
        $this->addMediaCollection('pieces_jointes')
            ->useDisk('public') // ou le disque que vous voulez
            ->acceptsMimeTypes([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'image/jpeg',
                'image/png',
                'image/gif',
                'application/zip',
            ])
            ->withResponsiveImages(); // pour les images uniquement

        // Si vous voulez une collection pour les images de cachet ou signature
         $this->addMediaCollection('cachet')->singleFile();
    }

    /**
     * Conversions d'images (optionnel)
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->sharpen(10)
            ->nonQueued();

        $this->addMediaConversion('preview')
            ->width(800)
            ->height(600)
            ->nonQueued();
    }
}
