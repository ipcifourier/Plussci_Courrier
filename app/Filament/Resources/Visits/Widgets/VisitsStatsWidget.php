<?php

namespace App\Filament\Resources\Visits\Widgets;

use App\Models\Visit;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class VisitsStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $userId = Auth::id();

        $total = Visit::query()->count();
        $aujourdhui = Visit::query()->whereDate('happened_at', now()->toDateString())->count();
        $cetteSemaine = Visit::query()
            ->whereBetween('happened_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
        $mesSaisies = $userId ? Visit::query()->where('created_by', $userId)->count() : 0;

        return [
            Stat::make('Total visites', (string) $total)
                ->icon('heroicon-o-users')
                ->color('primary'),
            Stat::make('Aujourdhui', (string) $aujourdhui)
                ->icon('heroicon-o-calendar-days')
                ->color('info'),
            Stat::make('Cette semaine', (string) $cetteSemaine)
                ->icon('heroicon-o-calendar')
                ->color('success'),
            Stat::make('Mes saisies', (string) $mesSaisies)
                ->icon('heroicon-o-user')
                ->color('warning'),
        ];
    }
}
