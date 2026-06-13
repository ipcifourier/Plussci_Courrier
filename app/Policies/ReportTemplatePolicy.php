<?php

namespace App\Policies;

use App\Models\ReportTemplate;
use App\Models\User;

class ReportTemplatePolicy
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
        return $user->can('reports.templates.manage');
    }

    public function view(User $user, ReportTemplate $template): bool
    {
        return $user->can('reports.templates.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('reports.templates.manage');
    }

    public function update(User $user, ReportTemplate $template): bool
    {
        return $user->can('reports.templates.manage');
    }

    public function delete(User $user, ReportTemplate $template): bool
    {
        return $user->can('reports.templates.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('reports.templates.manage');
    }
}
