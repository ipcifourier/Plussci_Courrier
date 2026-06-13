<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class MesTachesWidget extends StatsOverviewWidget
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
                Stat::make('Mes tâches', '0')
                    ->description('Utilisateur non connecté')
                    ->color('gray')
                    ->icon('heroicon-o-clipboard-document-list'),
            ];
        }

        $todo  = Task::where('assignee_id', $userId)->where('status', 'todo')->count();
        $doing = Task::where('assignee_id', $userId)->where('status', 'doing')->count();
        $done  = Task::where('assignee_id', $userId)->where('status', 'done')->count();

        $total = $todo + $doing;

        return [
            Stat::make('Mes tâches en cours', (string) $doing)
                ->description("{$todo} à faire · {$done} terminées")
                ->color($doing > 0 ? 'primary' : 'success')
                ->icon('heroicon-o-clipboard-document-list')
                ->url(route('filament.admin.resources.tasks.index')),
        ];
    }
}
