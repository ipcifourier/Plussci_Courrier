<x-filament-panels::page>
    @include('filament.components.pluss-theme-overrides')
    {{-- ───── Tabs ────────────────────────────────────────────────────────── --}}
    <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700 mb-6">
        <button
            wire:click="switchTab('documents')"
            @class([
                'px-4 py-2 text-sm font-medium border-b-2 transition-colors duration-150 -mb-px',
                'border-primary-600 text-primary-600 dark:text-primary-400 dark:border-primary-400' => $this->activeTab === 'documents',
                'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $this->activeTab !== 'documents',
            ])
        >
            <x-heroicon-o-document-text class="w-4 h-4 inline mr-1" />
            Documents GED
        </button>
        <button
            wire:click="switchTab('courriers')"
            @class([
                'px-4 py-2 text-sm font-medium border-b-2 transition-colors duration-150 -mb-px',
                'border-primary-600 text-primary-600 dark:text-primary-400 dark:border-primary-400' => $this->activeTab === 'courriers',
                'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $this->activeTab !== 'courriers',
            ])
        >
            <x-heroicon-o-rectangle-stack class="w-4 h-4 inline mr-1" />
            Courriers
        </button>

        @if($this->hasActiveFilters())
            <div class="ml-auto flex items-center">
                <button
                    wire:click="clearFilters"
                    class="text-xs text-gray-400 hover:text-danger-500 flex items-center gap-1 transition-colors"
                >
                    <x-heroicon-o-x-circle class="w-4 h-4" />
                    Réinitialiser les filtres
                </button>
            </div>
        @endif
    </div>

    {{-- ───── Search Form ──────────────────────────────────────────────────── --}}
    <div class="mb-6">
        {{ $this->form }}
    </div>

    {{-- ───── Results ──────────────────────────────────────────────────────── --}}
    @php
        $results = $this->getResults();
        $q = $this->filters['q'] ?? '';
        $service = app(\App\Services\SearchService::class);
    @endphp

    @if(! $this->hasActiveFilters())
        {{-- Empty state: invite to search --}}
        <div class="flex flex-col items-center justify-center py-20 text-center text-gray-400 dark:text-gray-500">
            <x-heroicon-o-magnifying-glass class="w-14 h-14 mb-4 opacity-30" />
            <p class="text-lg font-medium">Saisissez un terme ou appliquez un filtre</p>
            <p class="text-sm mt-1">La recherche porte sur les titres, références et le texte extrait par OCR.</p>
        </div>

    @elseif($results->total() === 0)
        {{-- No results --}}
        <div class="flex flex-col items-center justify-center py-16 text-center text-gray-400 dark:text-gray-500">
            <x-heroicon-o-face-frown class="w-12 h-12 mb-3 opacity-30" />
            <p class="text-base font-medium">Aucun résultat</p>
            <p class="text-sm mt-1">Essayez un terme différent ou élargissez vos filtres.</p>
        </div>

    @else
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ number_format($results->total()) }}</span>
                résultat{{ $results->total() > 1 ? 's' : '' }}
                @if($q) pour <span class="italic">«&nbsp;{{ e($q) }}&nbsp;»</span> @endif
            </p>
        </div>

        {{-- ─── Documents results ─────────────────────────────────────────── --}}
        @if($this->activeTab === 'documents')
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach($results as $doc)
                    @php
                        $version = $doc->currentVersion;
                        $hasOcr  = $version && $version->hasOcrText() && $q;
                        $snippet = $hasOcr ? $service->highlight($version->ocr_text, $q) : null;
                    @endphp
                    <a
                        href="{{ route('filament.admin.resources.documents.view', $doc) }}"
                        class="group flex flex-col gap-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 shadow-sm hover:shadow-md hover:border-primary-400 transition-all"
                    >
                        {{-- Header --}}
                        <div class="flex items-start gap-3">
                            <div class="shrink-0 mt-0.5">
                                <x-heroicon-o-document-text class="w-7 h-7 text-primary-500" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-sm text-gray-800 dark:text-gray-100 truncate group-hover:text-primary-600">
                                    @if($q)
                                        {!! $service->highlight($doc->titre, $q, 40) !!}
                                    @else
                                        {{ $doc->titre }}
                                    @endif
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5 font-mono">{{ $doc->reference_doc }}</p>
                            </div>
                        </div>

                        {{-- OCR snippet --}}
                        @if($snippet)
                            <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed line-clamp-3 border-l-2 border-yellow-300 pl-2">
                                {!! $snippet !!}
                            </p>
                        @endif

                        {{-- Meta badges --}}
                        <div class="flex flex-wrap gap-1.5 mt-auto pt-1">
                            <span class="text-xs rounded-full px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-500">
                                {{ $doc->type_document }}
                            </span>
                            @php
                                $etatColors = ['Brouillon' => 'bg-yellow-100 text-yellow-700', 'Valide' => 'bg-green-100 text-green-700', 'Archive' => 'bg-gray-200 text-gray-500'];
                            @endphp
                            <span class="text-xs rounded-full px-2 py-0.5 {{ $etatColors[$doc->etat_cycle_vie] ?? 'bg-gray-100 text-gray-500' }}">
                                {{ $doc->etat_cycle_vie }}
                            </span>
                            @if($doc->dossier)
                                <span class="text-xs rounded-full px-2 py-0.5 bg-blue-50 text-blue-600">
                                    {{ $doc->dossier->libelle }}
                                </span>
                            @endif
                            @if($version && $version->ocr_status === 'completed')
                                <span class="text-xs rounded-full px-2 py-0.5 bg-emerald-50 text-emerald-600" title="Texte indexé par OCR">
                                    OCR ✓
                                </span>
                            @endif
                        </div>

                        {{-- Date + auteur --}}
                        <div class="flex items-center justify-between text-xs text-gray-400 border-t border-gray-100 dark:border-gray-800 pt-2 mt-1">
                            <span>{{ $doc->auteur?->name ?? '—' }}</span>
                            <span>{{ $doc->updated_at->diffForHumans() }}</span>
                        </div>
                    </a>
                @endforeach
            </div>

        {{-- ─── Courriers results ─────────────────────────────────────────── --}}
        @else
            <div class="divide-y divide-gray-100 dark:divide-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                @foreach($results as $courrier)
                    <a
                        href="{{ route('filament.admin.resources.courriers.view', $courrier) }}"
                        class="group flex items-start gap-4 px-5 py-4 bg-white dark:bg-gray-900 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors"
                    >
                        {{-- Type badge --}}
                        <div class="shrink-0 mt-0.5">
                            @if($courrier->type === 'Entrant')
                                <x-heroicon-o-arrow-down-circle class="w-6 h-6 text-blue-500" />
                            @else
                                <x-heroicon-o-arrow-up-circle class="w-6 h-6 text-green-500" />
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-semibold text-sm text-gray-800 dark:text-gray-100 group-hover:text-primary-600">
                                    @if($q)
                                        {!! $service->highlight($courrier->objet, $q, 50) !!}
                                    @else
                                        {{ $courrier->objet }}
                                    @endif
                                </span>
                            </div>
                            <div class="flex items-center gap-3 mt-1 text-xs text-gray-400 flex-wrap">
                                <span class="font-mono">{{ $courrier->reference }}</span>
                                @if($courrier->correspondant)
                                    <span>{{ $courrier->correspondant->nom_structure }}</span>
                                @endif
                                <span>{{ $courrier->date_reception_envoi?->format('d/m/Y') }}</span>
                            </div>
                            @if($q && $courrier->resume)
                                <p class="text-xs text-gray-500 mt-1.5 leading-relaxed line-clamp-2">
                                    {!! $service->highlight($courrier->resume, $q) !!}
                                </p>
                            @endif
                        </div>

                        {{-- Right badges --}}
                        <div class="shrink-0 flex flex-col items-end gap-1.5">
                            @php
                                $statutColors = ['Nouveau' => 'bg-blue-100 text-blue-700', 'En cours' => 'bg-yellow-100 text-yellow-700', 'Traité' => 'bg-green-100 text-green-700', 'Archivé' => 'bg-gray-200 text-gray-500'];
                            @endphp
                            <span class="text-xs rounded-full px-2 py-0.5 {{ $statutColors[$courrier->statut] ?? 'bg-gray-100 text-gray-500' }}">
                                {{ $courrier->statut }}
                            </span>
                            <span class="text-xs text-gray-400">{{ $courrier->updated_at->diffForHumans() }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

        {{-- ─── Pagination ─────────────────────────────────────────────────── --}}
        @if($results->hasPages())
            <div class="mt-6">
                {{ $results->links() }}
            </div>
        @endif
    @endif

</x-filament-panels::page>
