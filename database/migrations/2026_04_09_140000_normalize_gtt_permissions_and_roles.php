<?php

use App\Services\GttRoleSyncService;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $responsablePermissions = GttRoleSyncService::responsablePermissions();
        $memberPermissions = GttRoleSyncService::memberPermissions();

        foreach (array_unique(array_merge($responsablePermissions, $memberPermissions)) as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $responsableRole = Role::firstOrCreate([
            'name' => GttRoleSyncService::RESPONSABLE_ROLE,
            'guard_name' => 'web',
        ]);
        $responsableRole->syncPermissions($responsablePermissions);

        $memberRole = Role::firstOrCreate([
            'name' => GttRoleSyncService::MEMBER_ROLE,
            'guard_name' => 'web',
        ]);
        $memberRole->syncPermissions($memberPermissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $roles = [
            GttRoleSyncService::RESPONSABLE_ROLE,
            GttRoleSyncService::MEMBER_ROLE,
        ];

        foreach ($roles as $roleName) {
            Role::query()->where('name', $roleName)->where('guard_name', 'web')->delete();
        }

        foreach (array_unique(array_merge(GttRoleSyncService::responsablePermissions(), GttRoleSyncService::memberPermissions())) as $permission) {
            Permission::query()->where('name', $permission)->where('guard_name', 'web')->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};