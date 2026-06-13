<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditLogPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_audit_view_permission_can_view_audit_logs(): void
    {
        Permission::findOrCreate('audit.view', 'web');

        $user = User::factory()->create();
        $user->givePermissionTo('audit.view');

        $auditLog = AuditLog::query()->create([
            'action' => 'courriers.export.pdf',
        ]);

        $this->assertTrue(Gate::forUser($user)->allows('viewAny', AuditLog::class));
        $this->assertTrue(Gate::forUser($user)->allows('view', $auditLog));
    }

    public function test_user_without_audit_permission_cannot_view_audit_logs(): void
    {
        $user = User::factory()->create();

        $this->assertFalse(Gate::forUser($user)->allows('viewAny', AuditLog::class));
    }

    public function test_super_admin_can_view_audit_logs_without_explicit_permission(): void
    {
        $superAdminRole = Role::findOrCreate('Super Admin', 'web');

        $user = User::factory()->create();
        $user->assignRole($superAdminRole);

        $this->assertTrue(Gate::forUser($user)->allows('viewAny', AuditLog::class));
    }
}
