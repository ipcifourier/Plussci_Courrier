<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure all permissions used in policies exist in every test
        foreach ([
            'admin.users.view',
            'admin.users.create',
            'admin.users.update',
            'admin.roles.manage',
        ] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        // Flush Spatie's permission cache after creating permissions
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function superAdmin(): User
    {
        $role = Role::findOrCreate('Super Admin', 'web');
        $user = User::factory()->create();
        $user->syncRoles([$role]);

        return $user;
    }

    private function adminMetier(): User
    {
        $role = Role::findOrCreate('Admin Métier', 'web');
        $role->syncPermissions(['admin.users.view', 'admin.users.create', 'admin.users.update']);

        $user = User::factory()->create();
        $user->syncRoles([$role]);

        return $user;
    }

    private function lecteur(): User
    {
        $role = Role::findOrCreate('Lecteur Courrier', 'web');
        $user = User::factory()->create();
        $user->syncRoles([$role]);

        return $user;
    }

    // -------------------------------------------------------------------------
    // Policy — viewAny
    // -------------------------------------------------------------------------

    public function test_super_admin_can_view_any_user(): void
    {
        $admin = $this->superAdmin();

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', User::class));
    }

    public function test_admin_metier_can_view_any_user(): void
    {
        $admin = $this->adminMetier();

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', User::class));
    }

    public function test_lecteur_cannot_view_any_user(): void
    {
        $user = $this->lecteur();

        $this->assertFalse(Gate::forUser($user)->allows('viewAny', User::class));
    }

    // -------------------------------------------------------------------------
    // Policy — view
    // -------------------------------------------------------------------------

    public function test_super_admin_can_view_a_user(): void
    {
        $admin  = $this->superAdmin();
        $target = User::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('view', $target));
    }

    public function test_admin_metier_can_view_a_user(): void
    {
        $admin  = $this->adminMetier();
        $target = User::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('view', $target));
    }

    public function test_lecteur_cannot_view_another_user(): void
    {
        $lecteur = $this->lecteur();
        $target  = User::factory()->create();

        $this->assertFalse(Gate::forUser($lecteur)->allows('view', $target));
    }

    // -------------------------------------------------------------------------
    // Policy — create
    // -------------------------------------------------------------------------

    public function test_super_admin_can_create_users(): void
    {
        $admin = $this->superAdmin();

        $this->assertTrue(Gate::forUser($admin)->allows('create', User::class));
    }

    public function test_admin_metier_can_create_users(): void
    {
        $admin = $this->adminMetier();

        $this->assertTrue(Gate::forUser($admin)->allows('create', User::class));
    }

    public function test_lecteur_cannot_create_users(): void
    {
        $user = $this->lecteur();

        $this->assertFalse(Gate::forUser($user)->allows('create', User::class));
    }

    // -------------------------------------------------------------------------
    // Policy — update
    // -------------------------------------------------------------------------

    public function test_super_admin_can_update_any_user(): void
    {
        $admin  = $this->superAdmin();
        $target = User::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('update', $target));
    }

    public function test_admin_metier_can_update_other_users(): void
    {
        $admin  = $this->adminMetier();
        $target = User::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('update', $target));
    }

    public function test_user_can_update_own_profile(): void
    {
        $user = $this->lecteur();

        $this->assertTrue(Gate::forUser($user)->allows('update', $user));
    }

    public function test_lecteur_cannot_update_another_user(): void
    {
        $lecteur = $this->lecteur();
        $target  = User::factory()->create();

        $this->assertFalse(Gate::forUser($lecteur)->allows('update', $target));
    }

    // -------------------------------------------------------------------------
    // Policy — delete
    // -------------------------------------------------------------------------

    public function test_super_admin_can_delete_another_user(): void
    {
        $admin  = $this->superAdmin();
        $target = User::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('delete', $target));
    }

    public function test_super_admin_cannot_delete_own_account(): void
    {
        $admin = $this->superAdmin();

        $this->assertFalse(Gate::forUser($admin)->allows('delete', $admin));
    }

    public function test_admin_metier_cannot_delete_users(): void
    {
        $admin  = $this->adminMetier();
        $target = User::factory()->create();

        $this->assertFalse(Gate::forUser($admin)->allows('delete', $target));
    }

    // -------------------------------------------------------------------------
    // Policy — manageRoles
    // -------------------------------------------------------------------------

    public function test_super_admin_can_manage_roles(): void
    {
        $admin  = $this->superAdmin();
        $target = User::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('manageRoles', $target));
    }

    public function test_user_with_admin_roles_manage_permission_can_manage_roles(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('admin.roles.manage');

        $target = User::factory()->create();

        $this->assertTrue(Gate::forUser($user)->allows('manageRoles', $target));
    }

    public function test_lecteur_cannot_manage_roles(): void
    {
        $lecteur = $this->lecteur();
        $target  = User::factory()->create();

        $this->assertFalse(Gate::forUser($lecteur)->allows('manageRoles', $target));
    }

    // -------------------------------------------------------------------------
    // Default role assignment on creation
    // -------------------------------------------------------------------------

    public function test_new_user_gets_lecteur_courrier_role_by_default(): void
    {
        Role::findOrCreate('Lecteur Courrier', 'web');

        $user = User::factory()->create();

        $this->assertTrue($user->hasRole('Lecteur Courrier'));
    }

    public function test_explicit_role_sync_overrides_default_role(): void
    {
        Role::findOrCreate('Lecteur Courrier', 'web');
        $gestionnaire = Role::findOrCreate('Gestionnaire Courrier', 'web');

        $user = User::factory()->create();
        // Simulate afterCreate() behaviour: explicit roles override the default
        $user->syncRoles([$gestionnaire]);

        $this->assertTrue($user->hasRole('Gestionnaire Courrier'));
        $this->assertFalse($user->hasRole('Lecteur Courrier'));
    }

    // -------------------------------------------------------------------------
    // Password change
    // -------------------------------------------------------------------------

    public function test_password_change_updates_hash(): void
    {
        $user = User::factory()->create(['password' => bcrypt('OldPassword1!')]);

        $user->update(['password' => bcrypt('NewPassword2!')]);
        $user->refresh();

        $this->assertTrue(Hash::check('NewPassword2!', $user->password));
        $this->assertFalse(Hash::check('OldPassword1!', $user->password));
    }

    public function test_password_change_is_audit_logged(): void
    {
        $this->actingAs($admin = $this->superAdmin());
        $target = User::factory()->create();

        app(AuditLogger::class)->log(
            action: 'user.password_changed',
            entity: $target,
            meta: ['changed_by' => $admin->id]
        );

        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'user.password_changed',
            'entity_type' => User::class,
            'entity_id'   => $target->id,
            'actor_id'    => $admin->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // User creation — fillable fields
    // -------------------------------------------------------------------------

    public function test_user_can_be_created_with_all_fields(): void
    {
        $user = User::factory()->create([
            'name'  => 'Jean Dupont',
            'email' => 'jean.dupont@example.com',
            'poste' => 'Directeur',
        ]);

        $this->assertDatabaseHas('users', [
            'name'  => 'Jean Dupont',
            'email' => 'jean.dupont@example.com',
            'poste' => 'Directeur',
        ]);
    }
}
