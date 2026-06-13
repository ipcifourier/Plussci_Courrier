<?php

namespace App\Filament\Widgets;

use App\Models\AuditLog;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AuditTopActorsWidget extends ChartWidget
{
    protected ?string $heading = 'Top 5 acteurs — ce mois';

    protected ?string $description = 'Utilisateurs avec le plus d\'actions enregistrées ce mois';

    protected ?string $maxHeight = '220px';

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
        return 'doughnut';
    }

    protected function getData(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();

        /** @var \Illuminate\Support\Collection $rows */
        $rows = AuditLog::query()
            ->select('actor_id')
            ->selectRaw('count(*) as action_count')
            ->where('created_at', '>=', $startOfMonth)
            ->groupBy('actor_id')
            ->orderByDesc('action_count')
            ->limit(5)
            ->with('actor:id,name')
            ->get();

        $labels = $rows->map(function ($row): string {
            if (! $row->actor_id) {
                return 'Système';
            }

            return optional($row->actor)->name ?? "User #{$row->actor_id}";
        })->all();

        $counts = $rows->pluck('action_count')->all();

        $colors = [
            '#f59e0b', // amber
            '#3b82f6', // blue
            '#10b981', // green
            '#ef4444', // red
            '#8b5cf6', // purple
        ];

        return [
            'datasets' => [
                [
                    'label'           => 'Actions',
                    'data'            => $counts,
                    'backgroundColor' => array_slice($colors, 0, count($counts)),
                ],
            ],
            'labels' => $labels,
        ];
    }
}
