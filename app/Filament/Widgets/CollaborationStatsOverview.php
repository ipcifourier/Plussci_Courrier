<?php

namespace App\Filament\Widgets;

use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class CollaborationStatsOverview extends StatsOverviewWidget
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
        $commentsThisWeek = Comment::where('created_at', '>=', now()->startOfWeek())->count();

        $activeTasks = Task::whereNotIn('status', ['done', 'cancelled'])->count();

        $closedThisWeek = Task::where('status', 'done')
            ->where('updated_at', '>=', now()->startOfWeek())
            ->count();

        $overdueAll = Task::whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['done', 'cancelled'])
            ->count();

        return [
            Stat::make('Commentaires (semaine)', (string) $commentsThisWeek)
                ->description('Activité collaborative cette semaine')
                ->color('info')
                ->icon('heroicon-o-chat-bubble-left-right'),

            Stat::make('Tâches actives', (string) $activeTasks)
                ->description("{$closedThisWeek} terminées cette semaine")
                ->color($activeTasks > 0 ? 'primary' : 'success')
                ->icon('heroicon-o-clipboard-document-list'),

            Stat::make('Tâches en retard', (string) $overdueAll)
                ->description('Toutes équipes confondues')
                ->color($overdueAll > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle'),
        ];
    }
}
