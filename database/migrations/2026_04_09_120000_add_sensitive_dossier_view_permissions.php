<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'ged.dossiers.view.confidential',
            'ged.dossiers.view.personal',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $rolePermissions = [
            'Admin Métier' => $permissions,
            'Archiviste GED' => $permissions,
            'Admin' => $permissions,
            'Gestionnaire Courrier' => ['ged.dossiers.view.confidential'],
            'GTT Responsable' => ['ged.dossiers.view.confidential'],
        ];

        foreach ($rolePermissions as $roleName => $grantedPermissions) {
            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->first();

            if (! $role) {
                continue;
            }

            foreach ($grantedPermissions as $permission) {
                $role->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissions = [
            'ged.dossiers.view.confidential',
            'ged.dossiers.view.personal',
        ];

        foreach ($permissions as $permission) {
            Permission::query()
                ->where('name', $permission)
                ->where('guard_name', 'web')
                ->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};