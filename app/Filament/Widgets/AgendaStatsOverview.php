<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Models\Meeting;
use App\Models\MeetingTask;
use App\Models\User;
use App\Models\Visit;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class AgendaStatsOverview extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->hasRole('Super Admin')
            || $user->hasAnyPermission([
                'agenda.viewAny',
                'agenda.view',
                'agenda.create',
                'agenda.update',
                'agenda.delete',
                'agenda.export',
                'agenda.meetings.manage',
                'agenda.appointments.manage',
                'agenda.visits.manage',
                'agenda.diligences.manage',
            ])
        );
    }

    protected function getStats(): array
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $weekEnd = now()->copy()->endOfWeek();

        $rdvToday = Appointment::query()
            ->whereBetween('starts_at', [$todayStart, $todayEnd])
            ->count();

        $visitesWeek = Visit::query()
            ->whereBetween('happened_at', [now()->startOfWeek(), $weekEnd])
            ->count();

        $reunionsWeek = Meeting::query()
            ->whereBetween('starts_at', [$todayStart, $weekEnd])
            ->whereIn('status', ['planned', 'postponed'])
            ->count();

        $diligencesRetard = MeetingTask::query()
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->where('status', '!=', 'done')
            ->count();

        return [
            Stat::make('Rendez-vous aujourd\'hui', (string) $rdvToday)
                ->description('Jour en cours')
                ->icon('heroicon-o-calendar-days')
                ->color($rdvToday > 0 ? 'info' : 'gray'),

            Stat::make('Visites (semaine)', (string) $visitesWeek)
                ->description('Depuis le debut de semaine')
                ->icon('heroicon-o-map-pin')
                ->color('success'),

            Stat::make('Reunions a venir', (string) $reunionsWeek)
                ->description('Planifiees ou reportees cette semaine')
                ->icon('heroicon-o-users')
                ->color('warning'),

            Stat::make('Diligences en retard', (string) $diligencesRetard)
                ->description('Taches de reunion non terminees')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($diligencesRetard > 0 ? 'danger' : 'success'),
        ];
    }
}
