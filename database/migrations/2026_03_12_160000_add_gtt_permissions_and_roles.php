<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        // Permissions spécifiques GTT
        $permissions = [
            'gtt.documents.view',
            'gtt.documents.manage',
            'gtt.members.view',
            'gtt.members.manage',
        ];
        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'web',
            ]);
        }

        // Rôles spécifiques GTT
        $roles = [
            'GTT Responsable',
            'GTT Membre',
        ];
        foreach ($roles as $role) {
            $roleModel = Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
            if ($role === 'GTT Responsable') {
                $roleModel->syncPermissions([
                    'gtt.documents.view',
                    'gtt.documents.manage',
                    'gtt.members.view',
                    'gtt.members.manage',
                ]);
            } else {
                $roleModel->syncPermissions([
                    'gtt.documents.view',
                    'gtt.members.view',
                ]);
            }
        }
    }

    public function down(): void
    {
        $roles = ['GTT Responsable', 'GTT Membre'];
        $permissions = [
            'gtt.documents.view',
            'gtt.documents.manage',
            'gtt.members.view',
            'gtt.members.manage',
        ];
        foreach ($roles as $role) {
            Role::where('name', $role)->delete();
        }
        foreach ($permissions as $perm) {
            Permission::where('name', $perm)->delete();
        }
    }
};
