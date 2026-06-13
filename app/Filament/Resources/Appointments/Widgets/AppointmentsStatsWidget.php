<?php

namespace App\Filament\Resources\Appointments\Widgets;

use App\Models\Appointment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class AppointmentsStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $userId = Auth::id();

        $total = Appointment::query()->count();
        $aVenir7Jours = Appointment::query()
            ->whereBetween('starts_at', [now(), now()->copy()->addDays(7)])
            ->count();
        $enRetard = Appointment::query()
            ->where('starts_at', '<', now())
            ->whereIn('status', ['planned', 'confirmed', 'rescheduled'])
            ->count();

        $mesRendezVous = $userId
            ? Appointment::query()
                ->where(function ($query) use ($userId): void {
                    $query->where('assigned_to', $userId)
                        ->orWhere('created_by', $userId);
                })
                ->count()
            : 0;

        return [
            Stat::make('Total', (string) $total)
                ->icon('heroicon-o-calendar-days')
                ->color('primary'),
            Stat::make('A venir (7 jours)', (string) $aVenir7Jours)
                ->icon('heroicon-o-clock')
                ->color('info'),
            Stat::make('En retard', (string) $enRetard)
                ->icon('heroicon-o-exclamation-triangle')
                ->color($enRetard > 0 ? 'danger' : 'success'),
            Stat::make('Mes rendez-vous', (string) $mesRendezVous)
                ->icon('heroicon-o-user')
                ->color('warning'),
        ];
    }
}
