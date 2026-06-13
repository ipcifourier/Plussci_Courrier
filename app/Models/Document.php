<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Document extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = [];

    protected $casts = [
        'tags_json'                  => 'array',
        'metadata_json'              => 'array',
        'classified_at'              => 'datetime',
        'locked_at'                  => 'datetime',
        'collaboration_enabled'      => 'boolean',
        'finalized_read_only_at'     => 'datetime',
        'classification_confidence'  => 'integer',
        'current_signature_level'    => 'integer',
        'reference_mode'             => 'string',
        'cloud_links'                => 'array',
    ];

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('Super Admin')) {
            return $query;
        }

        if ($user->hasRole('GTT Responsable')) {
            return $query
                ->where('gtt_id', $user->gtt?->id)
                ->where(function (Builder $dossierQuery) use ($user): void {
                    $dossierQuery->whereNull('dossier_id')
                        ->orWhereHas('dossier', fn (Builder $query) => $query->visibleTo($user));
                });
        }

        $userId = $user->id;
        $roleIds = $user->roles?->pluck('id')?->all() ?? [];

        return $query
            ->where(function (Builder $visibilityQuery) use ($userId, $roleIds): void {
                $visibilityQuery->whereNotExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('document_access_rules')
                        ->whereColumn('document_access_rules.document_id', 'documents.id')
                        ->where('document_access_rules.can_view', true);
                });

                if ($userId !== null) {
                    $visibilityQuery->orWhereExists(function ($subQuery) use ($userId) {
                        $subQuery->select(DB::raw(1))
                            ->from('document_access_rules')
                            ->whereColumn('document_access_rules.document_id', 'documents.id')
                            ->where('document_access_rules.can_view', true)
                            ->where('document_access_rules.user_id', $userId);
                    });
                }

                if ($roleIds !== []) {
                    $visibilityQuery->orWhereExists(function ($subQuery) use ($roleIds) {
                        $subQuery->select(DB::raw(1))
                            ->from('document_access_rules')
                            ->whereColumn('document_access_rules.document_id', 'documents.id')
                            ->where('document_access_rules.can_view', true)
                            ->whereIn('document_access_rules.role_id', $roleIds);
                    });
                }
            })
            ->where(function (Builder $dossierQuery) use ($user): void {
                $dossierQuery->whereNull('dossier_id')
                    ->orWhereHas('dossier', fn (Builder $query) => $query->visibleTo($user));
            });
    }

    // ── Classification helpers ────────────────────────────────────────────────

    public function isClassified(): bool
    {
        return $this->classified_at !== null;
    }

    public function classificationBadge(): string
    {
        if (! $this->isClassified()) {
            return 'Non classifié';
        }

        $confidence = $this->classification_confidence ?? 0;

        return match (true) {
            $confidence >= 75 => 'Auto (' . $confidence . '%)',
            $confidence >= 40 => 'Partiel (' . $confidence . '%)',
            default           => 'Faible (' . $confidence . '%)',
        };
    }

    // ── Tags helpers ─────────────────────────────────────────────────────────

    public function tagList(): array
    {
        return $this->tags_json ?? [];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents')
            ->useDisk('public')
            ->acceptsMimeTypes([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'image/jpeg',
                'image/png',
                'image/gif',
                'text/plain',
                'text/csv',
                'application/zip',
                'application/x-rar-compressed',
            ]);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // No image conversions needed for document files
    }

    public function dossier()
    {
        return $this->belongsTo(Dossier::class);
    }

    public function interventionDomain()
    {
        return $this->belongsTo(InterventionDomain::class, 'intervention_domain_id');
    }

    public function interventionSubdomain()
    {
        return $this->belongsTo(InterventionSubdomain::class, 'intervention_subdomain_id');
    }

    public function gtt()
    {
        return $this->belongsTo(Gtt::class);
    }

    public function courrier()
    {
        return $this->belongsTo(Courrier::class);
    }

    public function auteur()
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }

    public function currentVersion()
    {
        return $this->belongsTo(DocumentVersion::class, 'version_courante_id');
    }

    public function versions()
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable')->latest();
    }

    public function tasks()
    {
        return $this->morphMany(Task::class, 'taskable')->latest();
    }

    public function accessRules()
    {
        return $this->hasMany(DocumentAccessRule::class);
    }

    public function archiveRecord()
    {
        return $this->hasOne(\App\Models\ArchiveRecord::class);
    }

    public function shares()
    {
        return $this->hasMany(\App\Models\DocumentShare::class);
    }

    public function activeSessions()
    {
        return $this->hasMany(\App\Models\DocumentSession::class)
            ->where('last_seen_at', '>=', now()->subMinutes(5));
    }

    public function auditLogs()
    {
        return $this->hasMany(\App\Models\AuditLog::class, 'entity_id')
            ->where('entity_type', self::class)
            ->orderByDesc('created_at');
    }

    // ── Parapheur électronique ────────────────────────────────────────────────

    public function signatures()
    {
        return $this->hasMany(DocumentSignature::class)->orderBy('level');
    }

    /** Signataires au niveau courant du circuit. */
    public function currentSignatures()
    {
        return $this->hasMany(DocumentSignature::class)
            ->where('level', $this->current_signature_level);
    }

    /** Notification aux signataires du niveau courant. */
    public function notifyCurrentSignataires(): void
    {
        if (! $this->current_signature_level) {
            return;
        }

        $this->loadMissing('signatures.signataire');

        $this->signatures
            ->where('level', $this->current_signature_level)
            ->where('status', 'pending')
            ->each(fn (DocumentSignature $sig) => $sig->signataire?->notify(
                new \App\Notifications\DocumentSignatureRequestedNotification($this)
            ));
    }

    /** Notification à l'auteur du résultat du circuit. */
    public function notifyAuteurDecision(string $decision, ?string $comment = null): void
    {
        $this->loadMissing('auteur');

        $this->auteur?->notify(
            new \App\Notifications\DocumentSignatureDecisionNotification($this, $decision, $comment)
        );
    }

    /**
     * Démarre le circuit de parapheur.
     * Lance le premier niveau, met à jour le statut du document.
     */
    public function lancerParapheur(): void
    {
        $firstLevel = $this->signatures()->min('level');

        if (! $firstLevel) {
            return;
        }

        $this->update([
            'parapheur_status'        => 'pending',
            'current_signature_level' => $firstLevel,
        ]);

        $this->signatures()->update(['status' => 'pending', 'comment' => null, 'signed_at' => null]);
        $this->notifyCurrentSignataires();
    }

    public function isParapheurPending(): bool
    {
        return $this->parapheur_status === 'pending';
    }

    public function isParapheurCompleted(): bool
    {
        return $this->parapheur_status === 'completed';
    }

    // ── Locking helpers ───────────────────────────────────────────────────────

    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function finalizedReadOnlyBy()
    {
        return $this->belongsTo(User::class, 'finalized_read_only_by');
    }

    public function isReadOnlyFinalized(): bool
    {
        return $this->finalized_read_only_at !== null;
    }

    public function isCollaborativeEditingEnabled(): bool
    {
        return (bool) $this->collaboration_enabled && ! $this->isReadOnlyFinalized();
    }

    /**
     * True if locked by a User other than $user and the lock has not expired.
     */
    public function isLockedByOther(User $user, int $ttlMinutes = 30): bool
    {
        if ($this->locked_by === null || $this->locked_at === null) {
            return false;
        }

        if ($this->locked_at->addMinutes($ttlMinutes)->isPast()) {
            return false;
        }

        return $this->locked_by !== $user->id;
    }

    // ── Workflow (circuits de validation) ─────────────────────────────────────

    public function workflows()
    {
        return $this->hasMany(DocumentWorkflow::class)->orderByDesc('started_at');
    }

    public function activeWorkflow(): ?DocumentWorkflow
    {
        return $this->workflows()->where('status', 'pending')->latest('started_at')->first();
    }
}

