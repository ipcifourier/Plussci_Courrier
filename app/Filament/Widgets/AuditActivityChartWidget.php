<?php

namespace App\Filament\Widgets;

use App\Models\AuditLog;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AuditActivityChartWidget extends ChartWidget
{
    protected ?string $heading = 'Activité d\'audit — 14 derniers jours';

    protected ?string $description = 'Nombre d\'actions enregistrées par jour';

    protected ?string $maxHeight = '200px';

    public static function canView(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->getAllPermissions()->pluck('name')->contains('audit.view');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $today = Carbon::today();
        $days  = 14;

        $labels  = [];
        $counts  = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date     = $today->copy()->subDays($i);
            $labels[] = $date->format('d/m');
            $counts[] = AuditLog::query()
                ->whereDate('created_at', $date)
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Actions',
                    'data'            => $counts,
                    'borderColor'     => '#f59e0b',
                    'backgroundColor' => 'rgba(245,158,11,0.15)',
                    'fill'            => true,
                    'tension'         => 0.4,
                    'pointRadius'     => 3,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
