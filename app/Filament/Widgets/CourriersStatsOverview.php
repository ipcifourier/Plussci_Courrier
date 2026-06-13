<?php

namespace App\Filament\Widgets;

use App\Models\Courrier;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class CourriersStatsOverview extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->hasRole('Super Admin')
            || $user->hasAnyPermission([
                'courriers.viewAny',
                'courriers.view',
            ])
        );
    }

    protected function getStats(): array
    {
        $today = Carbon::today();

        $total = Courrier::query()->count();
        $nouveaux = Courrier::query()->where('statut', 'Nouveau')->count();
        $encours = Courrier::query()->where('statut', 'En cours')->count();
        $traites = Courrier::query()->where('statut', 'Traité')->count();
        $urgents = Courrier::query()->where('priorite', 'Urgente')->count();

        $monthlyTrend = collect(range(5, 0))
            ->map(fn (int $monthsAgo) => Courrier::query()
                ->whereBetween('created_at', [
                    $today->copy()->subMonths($monthsAgo)->startOfMonth(),
                    $today->copy()->subMonths($monthsAgo)->endOfMonth(),
                ])
                ->count())
            ->push(Courrier::query()->whereBetween('created_at', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()])->count())
            ->all();

        return [
            Stat::make('Total courriers', (string) $total)
                ->description('Volume global')
                ->icon('heroicon-o-document-text')
                ->chart($monthlyTrend),
            Stat::make('Nouveaux', (string) $nouveaux)
                ->description('À traiter')
                ->color('gray')
                ->icon('heroicon-o-inbox'),
            Stat::make('En cours', (string) $encours)
                ->description('Traitement actif')
                ->color('warning')
                ->icon('heroicon-o-clock'),
            Stat::make('Traités', (string) $traites)
                ->description('Finalisés')
                ->color('success')
                ->icon('heroicon-o-check-circle'),
            Stat::make('Urgents', (string) $urgents)
                ->description('Priorité urgente')
                ->color('danger')
                ->icon('heroicon-o-exclamation-triangle'),
        ];
    }
}
