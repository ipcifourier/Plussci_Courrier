<?php

namespace App\Filament\Widgets;

use App\Models\Courrier;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

/**
 * C1 — KPIs courriers pour le tableau de bord.
 */
class CourrierKpiStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $total      = Courrier::count();
        $enCours    = Courrier::whereNotIn('statut', ['Traité', 'Archivé'])->count();
        $traitesMois = Courrier::where('statut', 'Traité')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();
        $enRetard = Courrier::whereNotIn('statut', ['Traité', 'Archivé'])
            ->whereNotNull('delai_reponse')
            ->whereDate('delai_reponse', '<', Carbon::today())
            ->count();

        return [
            Stat::make('Total courriers', $total)
                ->description('Tous statuts confondus')
                ->icon('heroicon-o-inbox-stack')
                ->color('gray'),

            Stat::make('En cours', $enCours)
                ->description('Non traités / Non archivés')
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Traités ce mois', $traitesMois)
                ->description(Carbon::now()->isoFormat('MMMM YYYY'))
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('En retard', $enRetard)
                ->description('Délai dépassé')
                ->icon('heroicon-o-exclamation-circle')
                ->color('danger'),
        ];
    }
}
