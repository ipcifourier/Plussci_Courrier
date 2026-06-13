@php
    /** @var \App\Models\Dossier $record */
    $ancestors = $record->ancestorChain();
    $aggregatedDocumentsCount = $record->aggregatedDocumentsCount();
    $aggregatedChildrenCount = $record->aggregatedChildrenCount();
    $listUrl = \App\Filament\Resources\Dossiers\DossierResource::getUrl('index') . '?' . http_build_query(array_filter([
        'tableFilters[annee_activite][value]' => $record->annee_activite ? (string) $record->annee_activite : null,
        'tableFilters[dossier_cible][id]' => (string) $record->id,
    ]));
@endphp

<div class="space-y-4">
    <div class="rounded-xl border border-emerald-100 bg-emerald-50/80 p-4">
        <div class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-emerald-700">
            <span>Position GED</span>
            <span class="rounded-full bg-white px-2 py-1 text-[11px] text-emerald-700">Niveau {{ $record->hierarchy_level }}</span>
            @if ($record->annee_activite)
                <span class="rounded-full bg-white px-2 py-1 text-[11px] text-emerald-700">Annee {{ $record->annee_activite }}</span>
            @endif
        </div>

        <div class="text-sm font-medium text-slate-900">
            @forelse ($ancestors as $ancestor)
                <a class="text-slate-700 underline decoration-slate-300 underline-offset-4 hover:text-emerald-700" href="{{ \App\Filament\Resources\Dossiers\DossierResource::getUrl('view', ['record' => $ancestor]) }}">{{ $ancestor->libelle }}</a>
                <span class="px-1 text-slate-400">&gt;</span>
            @empty
                <span class="text-slate-500">Racine GED</span>
                <span class="px-1 text-slate-400">&gt;</span>
            @endforelse
            <a class="text-emerald-700 underline decoration-emerald-300 underline-offset-4" href="{{ \App\Filament\Resources\Dossiers\DossierResource::getUrl('view', ['record' => $record]) }}">{{ $record->libelle }}</a>
        </div>

        <div class="mt-3 flex flex-wrap gap-3 text-xs font-semibold">
            <a class="text-emerald-700 underline decoration-emerald-300 underline-offset-4" href="{{ \App\Filament\Resources\Dossiers\DossierResource::getUrl('view', ['record' => $record]) }}">Ouvrir la fiche</a>
            <a class="text-emerald-700 underline decoration-emerald-300 underline-offset-4" href="{{ $listUrl }}">Filtrer la liste GED</a>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-4">
        <h4 class="mb-3 text-sm font-semibold text-gray-900">Informations dossier</h4>
        <dl class="grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
            <div>
                <dt class="text-gray-500">Code</dt>
                <dd class="font-medium text-gray-900">{{ $record->code ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Libelle</dt>
                <dd class="font-medium text-gray-900">{{ $record->libelle ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Niveau de classement</dt>
                <dd class="font-medium text-gray-900">{{ $record->type_label }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Annee d'activite</dt>
                <dd class="font-medium text-gray-900">{{ $record->annee_activite ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Responsable</dt>
                <dd class="font-medium text-gray-900">{{ $record->owner?->name ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Parent</dt>
                <dd class="font-medium text-gray-900">
                    @if ($record->parent)
                        <a class="underline decoration-slate-300 underline-offset-4 hover:text-emerald-700" href="{{ \App\Filament\Resources\Dossiers\DossierResource::getUrl('view', ['record' => $record->parent]) }}">{{ $record->parent->libelle }}</a>
                    @else
                        -
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-gray-500">Confidentialite</dt>
                <dd class="font-medium text-gray-900">{{ $record->confidentialite ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Statut</dt>
                <dd class="font-medium text-gray-900">{{ $record->statut ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Documents</dt>
                <dd class="font-medium text-gray-900">{{ $record->documents()->count() }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Documents cumulés</dt>
                <dd class="font-medium text-gray-900">{{ $aggregatedDocumentsCount }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Sous-dossiers</dt>
                <dd class="font-medium text-gray-900">{{ $record->children()->count() }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Sous-dossiers cumulés</dt>
                <dd class="font-medium text-gray-900">{{ $aggregatedChildrenCount }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Fil d'Ariane</dt>
                <dd class="font-medium text-gray-900">
                    @forelse ($ancestors as $ancestor)
                        <a class="underline decoration-slate-300 underline-offset-4 hover:text-emerald-700" href="{{ \App\Filament\Resources\Dossiers\DossierResource::getUrl('view', ['record' => $ancestor]) }}">{{ $ancestor->libelle }}</a>
                        <span class="px-1 text-slate-400">&gt;</span>
                    @empty
                        <span>Racine GED</span>
                        <span class="px-1 text-slate-400">&gt;</span>
                    @endforelse
                    <a class="underline decoration-emerald-300 underline-offset-4 text-emerald-700" href="{{ \App\Filament\Resources\Dossiers\DossierResource::getUrl('view', ['record' => $record]) }}">{{ $record->libelle }}</a>
                </dd>
            </div>
            <div class="md:col-span-2">
                <dt class="text-gray-500">Description</dt>
                <dd class="whitespace-pre-wrap font-medium text-gray-900">{{ $record->description ?: '-' }}</dd>
            </div>
        </dl>
    </div>
</div>
