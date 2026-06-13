<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class TachesEnRetardWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->hasRole('Super Admin')
            || $user->hasAnyPermission([
                'collaboration.tasks.view',
                'collaboration.tasks.create',
                'collaboration.tasks.assign',
                'collaboration.tasks.update',
                'collaboration.tasks.close',
            ])
        );
    }

    protected function getStats(): array
    {
        $userId = Auth::id();

        if (! $userId) {
            return [
                Stat::make('Tâches en retard', '0')
                    ->description('Utilisateur non connecté')
                    ->color('gray')
                    ->icon('heroicon-o-exclamation-triangle'),
            ];
        }

        // Count overdue tasks assigned to current user
        $myOverdue = Task::where('assignee_id', $userId)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['done', 'cancelled'])
            ->count();

        // Count ALL overdue tasks (for admins / visibility)
        $allOverdue = Task::whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['done', 'cancelled'])
            ->count();

        return [
            Stat::make('Mes tâches en retard', (string) $myOverdue)
                ->description($allOverdue > 0 ? "{$allOverdue} en retard au total" : 'Aucun retard global')
                ->color($myOverdue > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle')
                ->url(route('filament.admin.resources.tasks.index')),
        ];
    }
}
