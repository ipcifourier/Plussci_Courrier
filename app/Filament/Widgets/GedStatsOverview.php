<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use App\Models\Dossier;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class GedStatsOverview extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->hasRole('Super Admin')
            || $user->hasAnyPermission([
                'ged.documents.view',
                'ged.dossiers.view',
            ])
        );
    }

    protected function getStats(): array
    {
        $brouillons = Document::where('etat_cycle_vie', 'Brouillon')->count();
        $valides     = Document::where('etat_cycle_vie', 'Valide')->count();
        $archives    = Document::where('etat_cycle_vie', 'Archive')->count();
        $dossiers    = Dossier::where('statut', 'Actif')->count();

        return [
            Stat::make('Dossiers actifs', (string) $dossiers)
                ->description('Dossiers GED ouverts')
                ->color('primary')
                ->icon('heroicon-o-folder-open'),

            Stat::make('Documents validés', (string) $valides)
                ->description("{$brouillons} en brouillon")
                ->color('success')
                ->icon('heroicon-o-document-check'),

            Stat::make('Documents archivés', (string) $archives)
                ->description('Cycle de vie terminé')
                ->color('gray')
                ->icon('heroicon-o-archive-box'),
        ];
    }
}
