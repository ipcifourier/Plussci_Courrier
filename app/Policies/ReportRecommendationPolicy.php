<?php

namespace App\Policies;

use App\Models\ReportRecommendation;
use App\Models\User;

class ReportRecommendationPolicy
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
        return $user->can('reports.recommendations.viewAny') || $user->can('reports.viewAny');
    }

    public function view(User $user, ReportRecommendation $recommendation): bool
    {
        return $user->can('reports.recommendations.viewAny') || $user->can('reports.view');
    }

    public function create(User $user): bool
    {
        return $user->can('reports.recommendations.create') || $user->can('reports.create');
    }

    public function update(User $user, ReportRecommendation $recommendation): bool
    {
        return $user->can('reports.recommendations.update') || $user->can('reports.update');
    }

    public function delete(User $user, ReportRecommendation $recommendation): bool
    {
        return $user->can('reports.recommendations.delete') || $user->can('reports.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('reports.recommendations.delete') || $user->can('reports.delete');
    }
}
