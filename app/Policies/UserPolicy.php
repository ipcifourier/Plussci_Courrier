<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $actor): bool
    {
        return $actor->hasRole('Super Admin') || $actor->hasPermissionTo('admin.users.view');
    }

    public function view(User $actor, User $user): bool
    {
        return $actor->hasRole('Super Admin') || $actor->hasPermissionTo('admin.users.view');
    }

    public function create(User $actor): bool
    {
        return $actor->hasRole('Super Admin') || $actor->hasPermissionTo('admin.users.create');
    }

    public function update(User $actor, User $user): bool
    {
        // Users can always update themselves (for profile / password change)
        if ($actor->id === $user->id) {
            return true;
        }

        return $actor->hasRole('Super Admin') || $actor->hasPermissionTo('admin.users.update');
    }

    public function delete(User $actor, User $user): bool
    {
        // Prevent self-deletion; only Super Admin can delete users
        return $actor->id !== $user->id && $actor->hasRole('Super Admin');
    }

    /**
     * Only Super Admin can manage roles/permissions assignments.
     */
    public function manageRoles(User $actor): bool
    {
        return $actor->hasRole('Super Admin') || $actor->hasPermissionTo('admin.roles.manage');
    }
}
