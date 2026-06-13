<?php

namespace App\Policies;

use App\Models\Courrier;
use App\Models\User;

class CourrierPolicy
{
    public function before(User $user, string $ability): bool | null
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('courriers.viewAny');
    }

    public function view(User $user, Courrier $courrier): bool
    {
        return $user->can('courriers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('courriers.create');
    }

    public function update(User $user, Courrier $courrier): bool
    {
        return $user->can('courriers.update');
    }

    public function delete(User $user, Courrier $courrier): bool
    {
        return $user->can('courriers.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('courriers.delete');
    }

    public function sign(User $user, Courrier $courrier): bool
    {
        return $user->can('courriers.sign');
    }

    public function export(User $user): bool
    {
        return $user->can('courriers.export');
    }
}
