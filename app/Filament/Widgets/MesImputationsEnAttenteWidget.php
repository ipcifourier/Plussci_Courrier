<?php

namespace App\Filament\Widgets;

use App\Models\Imputation;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class MesImputationsEnAttenteWidget extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->hasRole('Super Admin')
            || $user->hasRole('GTT Responsable')
            || $user->hasAnyPermission([
                'courriers.viewAny',
                'courriers.view',
            ])
        );
    }

    protected function getStats(): array
    {
        $userId = Auth::id();

        if (! $userId) {
            return [
                Stat::make('Mes imputations en attente', '0')
                    ->description('Utilisateur non connecté')
                    ->color('gray')
                    ->icon('heroicon-o-user'),
            ];
        }

        $enAttente = Imputation::query()
            ->where('destinataire_id', $userId)
            ->where('statut_traitement', 'En attente')
            ->count();

        $enCours = Imputation::query()
            ->where('destinataire_id', $userId)
            ->where('statut_traitement', 'En cours')
            ->count();

        return [
            Stat::make('Mes imputations en attente', (string) $enAttente)
                ->description("{$enCours} en cours")
                ->color($enAttente > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-inbox-stack')
                ->url(route('filament.admin.resources.courriers.mes-imputations')),
        ];
    }
}
