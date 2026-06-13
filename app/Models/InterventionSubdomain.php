<?php

namespace App\Models;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class InterventionSubdomain extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::created(function (self $subdomain): void {
            app(AuditLogger::class)->log(
                action: 'admin.intervention_subdomain.created',
                entity: $subdomain,
                after: Arr::except($subdomain->toArray(), ['created_at', 'updated_at'])
            );
        });

        static::updated(function (self $subdomain): void {
            $changes = Arr::except($subdomain->getChanges(), ['updated_at']);

            if ($changes === []) {
                return;
            }

            $before = [];
            foreach ($changes as $key => $value) {
                $before[$key] = $subdomain->getOriginal($key);
            }

            app(AuditLogger::class)->log(
                action: 'admin.intervention_subdomain.updated',
                entity: $subdomain,
                before: $before,
                after: $changes
            );
        });

        static::deleting(function (self $subdomain): void {
            if ($subdomain->documents()->exists()) {
                throw new \RuntimeException('Suppression impossible: ce sous-domaine est deja lie a des documents.');
            }
        });

        static::deleted(function (self $subdomain): void {
            app(AuditLogger::class)->log(
                action: 'admin.intervention_subdomain.deleted',
                entity: null,
                meta: [
                    'id' => $subdomain->id,
                    'name' => $subdomain->name,
                    'intervention_domain_id' => $subdomain->intervention_domain_id,
                ]
            );
        });
    }

    public function domain()
    {
        return $this->belongsTo(InterventionDomain::class, 'intervention_domain_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'intervention_subdomain_id');
    }
}
