<div class="space-y-3">

    {{-- Dossier actuel --}}
    <div class="flex items-start gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
        <x-heroicon-o-folder-open class="mt-0.5 h-5 w-5 shrink-0 text-amber-500" />
        <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Dossier sélectionné</p>
            <p class="mt-1 break-all font-mono text-sm text-gray-800">{{ $currentPath }}</p>
        </div>
    </div>

    {{-- Bouton remonter --}}
    @if($canGoUp)
    <button
        type="button"
        wire:click="scanBrowserGoUp"
        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 shadow-sm transition hover:bg-gray-50"
    >
        <x-heroicon-o-arrow-up class="h-3.5 w-3.5" />
        Dossier parent
    </button>
    @endif

    {{-- Liste des sous-dossiers --}}
    <div class="max-h-64 overflow-y-auto rounded-xl border border-gray-200 bg-white">
        @forelse($directories as $dir)
        <button
            type="button"
            wire:click="scanBrowserNavigate('{{ base64_encode($dir['path']) }}')"
            class="flex w-full items-center gap-3 border-b border-gray-100 px-4 py-2.5 text-left text-sm text-gray-700 last:border-b-0 transition hover:bg-primary-50 hover:text-primary-700"
        >
            <x-heroicon-o-folder class="h-4 w-4 shrink-0 text-amber-400" />
            <span class="truncate">{{ $dir['name'] }}</span>
            <x-heroicon-o-chevron-right class="ml-auto h-3.5 w-3.5 shrink-0 text-gray-300" />
        </button>
        @empty
        <div class="flex flex-col items-center justify-center gap-2 py-10 text-gray-400">
            <x-heroicon-o-folder-open class="h-8 w-8" />
            <p class="text-sm">Aucun sous-dossier dans ce répertoire</p>
        </div>
        @endforelse
    </div>

</div>
