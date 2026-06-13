<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Spatie\Permission\Models\Role;

/**
 * AD6 — Statistiques des rôles pour la liste d'administration.
 */
class RoleStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $roles      = Role::withCount('users', 'permissions')->get();
        $totalRoles = $roles->count();
        $totalUsers = $roles->sum('users_count');
        $topRole    = $roles->sortByDesc('users_count')->first();

        return [
            Stat::make('Rôles', $totalRoles)
                ->description('Rôles définis')
                ->icon('heroicon-o-shield-check')
                ->color('gray'),

            Stat::make('Utilisateurs assignés', $totalUsers)
                ->description('Total toutes assignations')
                ->icon('heroicon-o-users')
                ->color('info'),

            Stat::make('Rôle le plus utilisé', $topRole?->name ?? '-')
                ->description(($topRole?->users_count ?? 0) . ' utilisateur(s)')
                ->icon('heroicon-o-star')
                ->color('warning'),
        ];
    }
}
