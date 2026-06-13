<?php

namespace App\Filament\Widgets;

use App\Models\AuditLog;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AuditStatsOverview extends StatsOverviewWidget
{
    /**
     * Only visible to users with audit.view or Super Admin role.
     */
    public static function canView(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (! $user instanceof \App\Models\User) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->getAllPermissions()->pluck('name')->contains('audit.view');
    }

    protected function getStats(): array
    {
        $now   = Carbon::now();
        $today = Carbon::today();

        // Sparkline: daily counts for the last 7 days (D-6 → today)
        $dailySparkline = collect(range(6, 0))
            ->map(fn (int $daysAgo) => AuditLog::query()
                ->whereDate('created_at', $today->copy()->subDays($daysAgo))
                ->count())
            ->all();

        $totalActions = AuditLog::query()->count();

        $actionsToday = AuditLog::query()
            ->whereDate('created_at', $today)
            ->count();

        $actionsWeek = AuditLog::query()
            ->where('created_at', '>=', $now->copy()->subDays(7))
            ->count();

        $activeActorsMonth = AuditLog::query()
            ->where('created_at', '>=', $now->copy()->startOfMonth())
            ->whereNotNull('actor_id')
            ->distinct('actor_id')
            ->count('actor_id');

        // Anomaly detection: IPs with more than 30 actions in the last 24 hours
        $anomalousIps = AuditLog::query()
            ->select('ip_address')
            ->selectRaw('count(*) as cnt')
            ->where('created_at', '>=', $now->copy()->subDay())
            ->whereNotNull('ip_address')
            ->groupBy('ip_address')
            ->having('cnt', '>', 30)
            ->get()
            ->count();

        return [
            Stat::make('Total actions', number_format($totalActions, 0, ',', ' '))
                ->description('Toutes périodes confondues')
                ->icon('heroicon-o-clipboard-document-list')
                ->chart($dailySparkline)
                ->color('primary'),

            Stat::make('Aujourd\'hui', (string) $actionsToday)
                ->description('Actions enregistrées')
                ->icon('heroicon-o-calendar-days')
                ->color('info'),

            Stat::make('7 derniers jours', number_format($actionsWeek, 0, ',', ' '))
                ->description('Activité récente')
                ->icon('heroicon-o-chart-bar-square')
                ->color('warning'),

            Stat::make('Acteurs actifs (mois)', (string) $activeActorsMonth)
                ->description('Utilisateurs distincts')
                ->icon('heroicon-o-users')
                ->color('success'),

            Stat::make('Anomalies détectées', (string) $anomalousIps)
                ->description('IPs >30 actions / 24 h')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($anomalousIps > 0 ? 'danger' : 'success'),
        ];
    }
}
