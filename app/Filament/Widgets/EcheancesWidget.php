<?php

namespace App\Filament\Widgets;

use App\Models\Imputation;
use App\Models\Task;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class EcheancesWidget extends StatsOverviewWidget
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
                'courriers.viewAny',
                'courriers.view',
            ])
        );
    }

    protected function getStats(): array
    {
        $userId  = Auth::id();
        $horizon = Carbon::now()->addDays(7);
        $today   = Carbon::today();

        if (! $userId) {
            return [];
        }

        // Tâches dues dans les 7 prochains jours (assignées à moi)
        $tachesBientot = Task::where('assignee_id', $userId)
            ->whereNotNull('due_date')
            ->where('due_date', '>=', $today)
            ->where('due_date', '<=', $horizon)
            ->whereNotIn('status', ['done', 'cancelled'])
            ->count();

        // Tâches en retard (assignées à moi)
        $tachesRetard = Task::where('assignee_id', $userId)
            ->whereNotNull('due_date')
            ->where('due_date', '<', $today)
            ->whereNotIn('status', ['done', 'cancelled'])
            ->count();

        // Imputations en retard (assignées à moi)
        $imputationsRetard = Imputation::where('destinataire_id', $userId)
            ->whereNotNull('delai_traitement')
            ->where('delai_traitement', '<', $today)
            ->where('statut_traitement', '!=', 'Traité')
            ->count();

        // Imputations dues dans les 7 prochains jours (assignées à moi)
        $imputationsBientot = Imputation::where('destinataire_id', $userId)
            ->whereNotNull('delai_traitement')
            ->where('delai_traitement', '>=', $today)
            ->where('delai_traitement', '<=', $horizon)
            ->where('statut_traitement', '!=', 'Traité')
            ->count();

        return [
            Stat::make('Tâches à traiter (7j)', (string) $tachesBientot)
                ->description('Échéances dans les 7 prochains jours')
                ->color($tachesBientot > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-clock')
                ->url(route('filament.admin.resources.tasks.index')),

            Stat::make('Tâches en retard', (string) $tachesRetard)
                ->description($tachesRetard > 0 ? 'Action requise immédiatement' : 'Aucun retard')
                ->color($tachesRetard > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-circle')
                ->url(route('filament.admin.resources.tasks.index')),

            Stat::make('Imputations en retard', (string) $imputationsRetard)
                ->description($imputationsRetard > 0 ? 'Imputations dépassées' : 'Aucun retard')
                ->color($imputationsRetard > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-inbox-arrow-down')
                ->url(route('filament.admin.resources.courriers.index')),

            Stat::make('Imputations à traiter (7j)', (string) $imputationsBientot)
                ->description('Délais dans les 7 prochains jours')
                ->color($imputationsBientot > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-document-check')
                ->url(route('filament.admin.resources.courriers.index')),
        ];
    }
}
