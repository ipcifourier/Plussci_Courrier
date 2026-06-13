<?php

namespace App\Filament\Resources\Meetings\Widgets;

use App\Models\Meeting;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class MeetingsStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $userId = Auth::id();

        $total = Meeting::query()->count();
        $cetteSemaine = Meeting::query()
            ->whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
        $aVenir7Jours = Meeting::query()
            ->whereBetween('starts_at', [now(), now()->copy()->addDays(7)])
            ->count();
        $aTenirEnRetard = Meeting::query()
            ->where('starts_at', '<', now())
            ->whereIn('status', ['planned', 'postponed'])
            ->count();

        $mesReunions = $userId
            ? Meeting::query()
                ->where(function ($query) use ($userId): void {
                    $query->where('facilitator_id', $userId)
                        ->orWhereHas('participants', fn ($sub) => $sub->where('users.id', $userId));
                })
                ->count()
            : 0;

        return [
            Stat::make('Total reunions', (string) $total)
                ->icon('heroicon-o-calendar-days')
                ->color('primary'),
            Stat::make('Cette semaine', (string) $cetteSemaine)
                ->icon('heroicon-o-calendar')
                ->color('info'),
            Stat::make('A venir (7 jours)', (string) $aVenir7Jours)
                ->icon('heroicon-o-clock')
                ->color('success'),
            Stat::make('A tenir en retard', (string) $aTenirEnRetard)
                ->icon('heroicon-o-exclamation-triangle')
                ->color($aTenirEnRetard > 0 ? 'danger' : 'success'),
            Stat::make('Mes reunions', (string) $mesReunions)
                ->icon('heroicon-o-user')
                ->color('warning'),
        ];
    }
}
