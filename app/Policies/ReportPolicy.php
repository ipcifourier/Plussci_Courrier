<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
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
        return $user->can('reports.viewAny');
    }

    public function view(User $user, Report $report): bool
    {
        return $user->can('reports.view');
    }

    public function create(User $user): bool
    {
        return $user->can('reports.create');
    }

    public function update(User $user, Report $report): bool
    {
        return $user->can('reports.update');
    }

    public function delete(User $user, Report $report): bool
    {
        return $user->can('reports.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('reports.delete');
    }
}
