<?php

namespace App\Filament\Widgets;

use App\Models\Courrier;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/**
 * C1 — Tendance courriers entrants vs sortants sur 12 mois.
 */
class CourrierTrendChartWidget extends ChartWidget
{
    protected ?string $heading = 'Courriers : Entrants vs Sortants (12 mois)';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $labels  = [];
        $entrants = [];
        $sortants = [];

        for ($i = 11; $i >= 0; $i--) {
            $month    = Carbon::now()->subMonths($i);
            $labels[] = $month->isoFormat('MMM YY');

            $entrants[] = Courrier::where('type', 'Entrant')
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();

            $sortants[] = Courrier::where('type', 'Sortant')
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
        }

        return [
            'labels'   => $labels,
            'datasets' => [
                [
                    'label'           => 'Entrants',
                    'data'            => $entrants,
                    'backgroundColor' => '#2563eb',
                ],
                [
                    'label'           => 'Sortants',
                    'data'            => $sortants,
                    'backgroundColor' => '#d97706',
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
