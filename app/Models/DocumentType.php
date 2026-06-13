<?php

namespace App\Models;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class DocumentType extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::created(function (self $type): void {
            app(AuditLogger::class)->log(
                action: 'admin.document_type.created',
                entity: $type,
                after: Arr::except($type->toArray(), ['created_at', 'updated_at'])
            );
        });

        static::updated(function (self $type): void {
            $changes = Arr::except($type->getChanges(), ['updated_at']);

            if ($changes === []) {
                return;
            }

            $before = [];
            foreach ($changes as $key => $value) {
                $before[$key] = $type->getOriginal($key);
            }

            app(AuditLogger::class)->log(
                action: 'admin.document_type.updated',
                entity: $type,
                before: $before,
                after: $changes
            );
        });

        static::deleting(function (self $type): void {
            if ($type->documents()->exists()) {
                throw new \RuntimeException('Suppression impossible: ce type est deja lie a des documents.');
            }
        });

        static::deleted(function (self $type): void {
            app(AuditLogger::class)->log(
                action: 'admin.document_type.deleted',
                entity: null,
                meta: [
                    'id' => $type->id,
                    'name' => $type->name,
                ]
            );
        });
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'type_document', 'name');
    }
}
