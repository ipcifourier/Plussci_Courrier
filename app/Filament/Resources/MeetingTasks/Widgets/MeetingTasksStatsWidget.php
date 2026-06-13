<?php

namespace App\Filament\Resources\MeetingTasks\Widgets;

use App\Models\MeetingTask;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class MeetingTasksStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $userId = Auth::id();

        $ouvertes = MeetingTask::query()->where('status', '!=', 'done')->count();
        $enRetard = MeetingTask::query()
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->where('status', '!=', 'done')
            ->count();
        $echeanceJour = MeetingTask::query()
            ->whereDate('due_at', now()->toDateString())
            ->where('status', '!=', 'done')
            ->count();
        $urgentes = MeetingTask::query()
            ->where('priority', 'urgent')
            ->where('status', '!=', 'done')
            ->count();

        $mesDiligences = $userId
            ? MeetingTask::query()->where('assigned_to', $userId)->where('status', '!=', 'done')->count()
            : 0;

        return [
            Stat::make('Diligences ouvertes', (string) $ouvertes)
                ->icon('heroicon-o-clipboard-document-list')
                ->color('primary'),
            Stat::make('En retard', (string) $enRetard)
                ->icon('heroicon-o-exclamation-triangle')
                ->color($enRetard > 0 ? 'danger' : 'success'),
            Stat::make('Echeance du jour', (string) $echeanceJour)
                ->icon('heroicon-o-calendar-days')
                ->color('warning'),
            Stat::make('Urgentes', (string) $urgentes)
                ->icon('heroicon-o-bell-alert')
                ->color($urgentes > 0 ? 'danger' : 'gray'),
            Stat::make('Mes diligences', (string) $mesDiligences)
                ->icon('heroicon-o-user')
                ->color('info'),
        ];
    }
}
