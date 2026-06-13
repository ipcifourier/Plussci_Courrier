<?php

namespace App\Models;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class InterventionDomain extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::created(function (self $domain): void {
            app(AuditLogger::class)->log(
                action: 'admin.intervention_domain.created',
                entity: $domain,
                after: Arr::except($domain->toArray(), ['created_at', 'updated_at'])
            );
        });

        static::updated(function (self $domain): void {
            $changes = Arr::except($domain->getChanges(), ['updated_at']);

            if ($changes === []) {
                return;
            }

            $before = [];
            foreach ($changes as $key => $value) {
                $before[$key] = $domain->getOriginal($key);
            }

            app(AuditLogger::class)->log(
                action: 'admin.intervention_domain.updated',
                entity: $domain,
                before: $before,
                after: $changes
            );
        });

        static::deleting(function (self $domain): void {
            if ($domain->documents()->exists()) {
                throw new \RuntimeException('Suppression impossible: ce domaine est deja lie a des documents.');
            }

            if ($domain->subdomains()->exists()) {
                throw new \RuntimeException('Suppression impossible: ce domaine contient des sous-domaines.');
            }
        });

        static::deleted(function (self $domain): void {
            app(AuditLogger::class)->log(
                action: 'admin.intervention_domain.deleted',
                entity: null,
                meta: [
                    'id' => $domain->id,
                    'name' => $domain->name,
                ]
            );
        });
    }

    public function subdomains()
    {
        return $this->hasMany(InterventionSubdomain::class)->orderBy('name');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'intervention_domain_id');
    }
}
