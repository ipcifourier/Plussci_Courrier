<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public function log(
        string $action,
        ?Model $entity = null,
        array $before = [],
        array $after = [],
        array $meta = [],
    ): AuditLog {
        $request = request();

        return AuditLog::query()->create([
            'actor_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $entity ? $entity::class : null,
            'entity_id' => $entity?->getKey(),
            'before_json' => empty($before) ? null : $before,
            'after_json' => empty($after) ? null : $after,
            'meta_json' => empty($meta) ? null : $meta,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
