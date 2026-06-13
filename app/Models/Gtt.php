<?php

namespace App\Models;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class Gtt extends Model
{
    protected $fillable = ['name', 'responsable', 'description'];

    protected $casts = [
        'responsable' => 'integer',
    ];

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('Super Admin') || $user->hasPermissionTo('admin.roles.manage')) {
            return $query;
        }

        if ($user->hasRole('GTT Responsable')) {
            return $query->where('responsable', $user->id);
        }

        if ($user->hasPermissionTo('gtt.documents.view') || $user->hasPermissionTo('gtt.members.view')) {
            return $query;
        }

        return $query->whereRaw('1 = 0');
    }

    public function responsableUser()
    {
        return $this->belongsTo(User::class, 'responsable');
    }

    public function canBeViewedBy(User $user): bool
    {
        if ($user->hasRole('Super Admin') || $user->hasPermissionTo('admin.roles.manage')) {
            return true;
        }

        if ($user->hasRole('GTT Responsable')) {
            return (int) $this->responsable === (int) $user->id;
        }

        if ($user->hasPermissionTo('gtt.documents.view') || $user->hasPermissionTo('gtt.members.view')) {
            return true;
        }

        return false;
    }

    public function canBeManagedBy(User $user): bool
    {
        if ($user->hasRole('Super Admin') || $user->hasPermissionTo('admin.roles.manage')) {
            return true;
        }

        if ($user->hasRole('GTT Responsable')) {
            return (int) $this->responsable === (int) $user->id;
        }

        if ($user->hasPermissionTo('gtt.members.manage')) {
            return true;
        }

        return false;
    }

    public function bureauMembers()
    {
        return $this->hasMany(BureauMember::class);
    }

    protected static function booted(): void
    {
        static::created(function (self $gtt): void {
            app(AuditLogger::class)->log(
                action: 'admin.gtt.created',
                entity: $gtt,
                after: Arr::except($gtt->toArray(), ['created_at', 'updated_at'])
            );
        });

        static::updated(function (self $gtt): void {
            $changes = Arr::except($gtt->getChanges(), ['updated_at']);

            if ($changes === []) {
                return;
            }

            $before = [];
            foreach ($changes as $key => $value) {
                $before[$key] = $gtt->getOriginal($key);
            }

            app(AuditLogger::class)->log(
                action: 'admin.gtt.updated',
                entity: $gtt,
                before: $before,
                after: $changes
            );
        });

        static::deleting(function (self $gtt): void {
            if ($gtt->documents()->exists()) {
                throw new \RuntimeException('Suppression impossible: ce GTT est deja lie a des documents.');
            }
        });

        static::deleted(function (self $gtt): void {
            app(AuditLogger::class)->log(
                action: 'admin.gtt.deleted',
                entity: null,
                meta: [
                    'id' => $gtt->id,
                    'name' => $gtt->name,
                ]
            );
        });
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }
}
