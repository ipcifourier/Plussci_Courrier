<?php

namespace App\Filament\Resources\Dossiers\Widgets;

use App\Filament\Resources\Dossiers\DossierResource;
use App\Filament\Pages\AcquisitionPage;
use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Dossier;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DossiersTreeWidget extends Widget
{
    protected string $view = 'filament.widgets.dossiers-tree-widget';

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User;
    }

    protected function getViewData(): array
    {
        $records = Dossier::query()
            ->visibleTo(Auth::user())
            ->with('owner')
            ->withCount(['documents', 'children'])
            ->orderByDesc('annee_activite')
            ->orderBy('parent_id')
            ->orderBy('ordre_affichage')
            ->orderBy('libelle')
            ->get();

        $nodesByParent = $records->groupBy(fn (Dossier $dossier) => $dossier->parent_id ?? 0);
        $currentYear = (int) now()->format('Y');

        $years = $records
            ->pluck('annee_activite')
            ->filter()
            ->unique()
            ->sortDesc()
            ->values()
            ->map(function (int $year) use ($records, $nodesByParent, $currentYear): array {
                $nodes = $records
                    ->filter(function (Dossier $dossier) use ($year): bool {
                        return $dossier->annee_activite === $year
                            && ($dossier->parent_id === null || $dossier->parent?->annee_activite !== $year);
                    })
                    ->values()
                    ->map(fn (Dossier $dossier): array => $this->buildNode($dossier, $nodesByParent))
                    ->all();

                return [
                    'year' => $year,
                    'label' => (string) $year,
                    'is_open' => $year === $currentYear,
                    'nodes' => $nodes,
                    'count' => count($nodes),
                ];
            })
            ->all();

        $unclassifiedNodes = $records
            ->filter(fn (Dossier $dossier): bool => $dossier->annee_activite === null && $dossier->parent_id === null)
            ->values()
            ->map(fn (Dossier $dossier): array => $this->buildNode($dossier, $nodesByParent))
            ->all();

        return [
            'yearGroups' => $years,
            'unclassifiedNodes' => $unclassifiedNodes,
            'totalDossiers' => $records->count(),
            'classifiedDossiers' => $records->whereNotNull('annee_activite')->count(),
        ];
    }

    protected function buildNode(Dossier $dossier, Collection $nodesByParent): array
    {
        $children = ($nodesByParent->get($dossier->id) ?? collect())
            ->map(fn (Dossier $child): array => $this->buildNode($child, $nodesByParent))
            ->all();

        $aggregatedDocumentsCount = (int) $dossier->documents_count + array_sum(array_column($children, 'aggregated_documents_count'));
        $aggregatedChildrenCount = (int) $dossier->children_count + array_sum(array_column($children, 'aggregated_children_count'));

        return [
            'id' => $dossier->id,
            'label' => $dossier->libelle,
            'code' => $dossier->code,
            'type_label' => $dossier->type_label,
            'documents_count' => (int) $dossier->documents_count,
            'children_count' => (int) $dossier->children_count,
            'aggregated_documents_count' => $aggregatedDocumentsCount,
            'aggregated_children_count' => $aggregatedChildrenCount,
            'owner' => $dossier->owner?->name,
            'url' => DossierResource::getUrl('view', ['record' => $dossier]),
            'edit_url' => DossierResource::getUrl('edit', ['record' => $dossier]),
            'create_child_url' => DossierResource::getUrl('create') . '?' . http_build_query(array_filter([
                'parent_id' => (string) $dossier->id,
                'annee_activite' => $dossier->annee_activite ? (string) $dossier->annee_activite : null,
                'type_dossier' => $dossier->type_dossier === Dossier::TYPE_YEAR ? Dossier::TYPE_CATEGORY : Dossier::TYPE_SUBCATEGORY,
                'ordre_affichage' => (string) ((((int) $dossier->children_count) + 1) * 10),
            ])),
            'create_document_url' => DocumentResource::getUrl('create') . '?' . http_build_query([
                'dossier_id' => (string) $dossier->id,
            ]),
            'acquisition_url' => AcquisitionPage::getUrl([
                'dossier_id' => (string) $dossier->id,
            ]),
            'filter_url' => $this->listUrl(array_filter([
                'tableFilters[annee_activite][value]' => $dossier->annee_activite ? (string) $dossier->annee_activite : null,
                'tableFilters[dossier_cible][id]' => (string) $dossier->id,
            ])),
            'children' => $children,
            'default_open' => $dossier->type_dossier === Dossier::TYPE_YEAR,
        ];
    }

    /**
     * @param array<string, string> $query
     */
    protected function listUrl(array $query = []): string
    {
        $base = DossierResource::getUrl('index');

        if (empty($query)) {
            return $base;
        }

        return $base . '?' . http_build_query($query);
    }
}