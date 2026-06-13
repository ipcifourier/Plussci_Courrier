<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * AD1 — Statistiques utilisateurs pour la liste d'administration.
 */
class UserStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $total    = User::count();
        $active   = User::where('is_active', true)->count();
        $inactive = User::where('is_active', false)->count();
        $neverLogged = User::whereNull('last_login_at')->count();

        return [
            Stat::make('Utilisateurs', $total)
                ->description('Total comptes créés')
                ->icon('heroicon-o-users')
                ->color('gray'),

            Stat::make('Actifs', $active)
                ->description('Comptes actifs')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Inactifs', $inactive)
                ->description('Comptes désactivés')
                ->icon('heroicon-o-x-circle')
                ->color('danger'),

            Stat::make('Jamais connectés', $neverLogged)
                ->description('Sans connexion enregistrée')
                ->icon('heroicon-o-question-mark-circle')
                ->color('warning'),
        ];
    }
}
