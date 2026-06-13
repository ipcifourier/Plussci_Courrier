<?php

namespace App\Policies;

use App\Models\Dossier;
use App\Models\User;

class DossierPolicy
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
        return $user->can('ged.dossiers.view');
    }

    public function view(User $user, Dossier $dossier): bool
    {
        return $dossier->isVisibleTo($user);
    }

    public function create(User $user): bool
    {
        return $user->can('ged.dossiers.create');
    }

    public function update(User $user, Dossier $dossier): bool
    {
        return $user->can('ged.dossiers.update');
    }

    public function delete(User $user, Dossier $dossier): bool
    {
        return $user->can('ged.dossiers.archive');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('ged.dossiers.archive');
    }
}
