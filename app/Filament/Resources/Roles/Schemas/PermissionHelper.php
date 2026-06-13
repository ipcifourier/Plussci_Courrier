<?php

namespace App\Filament\Resources\Roles\Schemas;

use Spatie\Permission\Models\Permission;

class PermissionHelper
{
    /**
     * Retourne la liste des permissions existantes en base (nom).
     */
    public static function getExistingPermissions(): array
    {
        return Permission::pluck('name')->toArray();
    }
}
