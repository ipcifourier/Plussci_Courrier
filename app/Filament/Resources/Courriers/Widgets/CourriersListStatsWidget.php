<?php

namespace App\Filament\Resources\Courriers\Widgets;

use App\Filament\Resources\Courriers\CourrierResource;
use App\Models\Correspondant;
use App\Models\Courrier;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class CourriersListStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $total = Courrier::query()->count();
        $enCours = Courrier::query()->where('statut', 'En cours')->count();
        $enRetard = Courrier::query()
            ->whereNotNull('delai_reponse')
            ->whereDate('delai_reponse', '<', now())
            ->whereNotIn('statut', ['Traité', 'Archivé'])
            ->count();
        $aValider = Courrier::query()->where('approval_status', 'pending')->count();
        $signes = Courrier::query()->whereNotNull('signed_at')->count();

        $topCorrespondantRow = Courrier::query()
            ->selectRaw('correspondant_id, COUNT(*) as total')
            ->whereNotNull('correspondant_id')
            ->groupBy('correspondant_id')
            ->orderByDesc('total')
            ->first();

        $topCorrespondantName = 'Aucun';
        $topCorrespondantCount = 0;
        $topCorrespondantId = null;

        if ($topCorrespondantRow) {
            $topCorrespondantId = (int) $topCorrespondantRow->correspondant_id;
            $topCorrespondantCount = (int) $topCorrespondantRow->total;

            $topCorrespondant = Correspondant::query()->find($topCorrespondantId);
            $topCorrespondantName = $topCorrespondant?->nom_structure ?? 'Correspondant #' . $topCorrespondantId;
        }

        $today = Carbon::today();
        $currentStart = $today->copy()->subDays(29);
        $previousStart = $today->copy()->subDays(59);
        $previousEnd = $today->copy()->subDays(30);

        $sparkline = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = $today->copy()->subDays($i);

            $sparkline[] = Courrier::query()
                ->whereDate('date_reception_envoi', $day)
                ->count();
        }

        $current30 = Courrier::query()
            ->whereDate('date_reception_envoi', '>=', $currentStart)
            ->whereDate('date_reception_envoi', '<=', $today)
            ->count();

        $previous30 = Courrier::query()
            ->whereDate('date_reception_envoi', '>=', $previousStart)
            ->whereDate('date_reception_envoi', '<=', $previousEnd)
            ->count();

        $trendDiff = $current30 - $previous30;
        $trendDescription = $trendDiff === 0
            ? 'Stable vs 30 jours precedents'
            : (($trendDiff > 0 ? '+' : '') . $trendDiff . ' vs 30 jours precedents');

        return [
            Stat::make('Total courriers', (string) $total)
                ->description('Vue globale')
                ->color('primary')
                ->icon('heroicon-o-inbox-stack')
                ->url($this->listUrl()),

            Stat::make('En cours', (string) $enCours)
                ->description('Suivi opérationnel')
                ->color('warning')
                ->icon('heroicon-o-clock')
                ->url($this->listUrl([
                    'tableFilters[statut][value]' => 'En cours',
                ])),

            Stat::make('En retard', (string) $enRetard)
                ->description('Délais dépassés')
                ->color($enRetard > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle')
                ->url($this->listUrl([
                    'tableFilters[en_retard][isActive]' => '1',
                ])),

            Stat::make('A valider', (string) $aValider)
                ->description('Workflow approbation')
                ->color('info')
                ->icon('heroicon-o-check-badge')
                ->url($this->listUrl([
                    'tableFilters[approval_status][value]' => 'pending',
                ])),

            Stat::make('Signes', (string) $signes)
                ->description('Courriers sortants signes')
                ->color('success')
                ->icon('heroicon-o-pencil-square')
                ->url($this->listUrl([
                    'tableFilters[signed_only][isActive]' => '1',
                ])),

            Stat::make('Top correspondant', (string) $topCorrespondantCount)
                ->description($topCorrespondantName)
                ->color('gray')
                ->icon('heroicon-o-building-office')
                ->url($this->listUrl($topCorrespondantId
                    ? ['tableFilters[correspondant_id][value]' => (string) $topCorrespondantId]
                    : []
                )),

            Stat::make('Tendance 30 jours', (string) $current30)
                ->description($trendDescription)
                ->color($trendDiff >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-chart-bar')
                ->chart($sparkline)
                ->url($this->listUrl([
                    'tableFilters[date_reception_envoi][date_debut]' => $currentStart->toDateString(),
                    'tableFilters[date_reception_envoi][date_fin]' => $today->toDateString(),
                ])),
        ];
    }

    /**
     * @param array<string, string> $query
     */
    private function listUrl(array $query = []): string
    {
        $base = CourrierResource::getUrl('index');

        if (empty($query)) {
            return $base;
        }

        return $base . '?' . http_build_query($query);
    }
}
