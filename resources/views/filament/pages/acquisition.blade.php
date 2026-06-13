<x-filament-panels::page>
    @once
        <style>
            .acquisition-ocr-page .text-slate-900 { color: #0f172a !important; }
            .acquisition-ocr-page .text-slate-700 { color: #334155 !important; }
            .acquisition-ocr-page .text-slate-600 { color: #475569 !important; }
            .acquisition-ocr-page .text-slate-500 { color: #64748b !important; }
            .acquisition-ocr-page .text-sky-700 { color: #0369a1 !important; }
            .acquisition-ocr-page .text-emerald-700 { color: #047857 !important; }
            .acquisition-ocr-page .text-amber-700 { color: #b45309 !important; }
            .acquisition-ocr-page .text-amber-800 { color: #92400e !important; }
            .acquisition-ocr-page .text-white { color: #ffffff !important; }
        </style>
    @endonce

    <div class="acquisition-ocr-page space-y-6">
        <section class="rounded-3xl border border-sky-100 bg-gradient-to-br from-sky-50 via-white to-cyan-50 p-6 shadow-sm">
            <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
                <div class="max-w-3xl space-y-3">
                    <span class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-sky-700">
                        Acquisition OCR
                    </span>
                    <h2 class="text-2xl font-semibold tracking-tight text-slate-900">
                        Scanner par lot, détecter, prévisualiser et transférer rapidement vers la GED.
                    </h2>
                    <p class="text-sm leading-6 text-slate-600">
                        Cette interface combine import multi-documents, OCR, détection automatique du type, proposition de classement intelligent et flux scanner par dossier surveillé.
                    </p>

                    @if ($this->selectedDossierContext)
                        <div class="rounded-2xl border border-emerald-200 bg-white/90 p-4 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Dossier GED présélectionné</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ $this->selectedDossierContext['path'] }}</p>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                <span class="rounded-full bg-emerald-50 px-3 py-1 font-semibold text-emerald-700">{{ $this->selectedDossierContext['type'] }}</span>
                                <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-700">{{ $this->selectedDossierContext['documents'] }} doc. cumulés</span>
                                <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-700">{{ $this->selectedDossierContext['children'] }} sous-dossier(s)</span>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="grid min-w-full gap-3 sm:grid-cols-2 xl:min-w-[28rem]">
                    <div class="rounded-2xl border border-white/80 bg-white/90 p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">OCR en attente</p>
                        <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $this->acquisitionStats['pending_ocr'] }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/80 bg-white/90 p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">OCR terminé</p>
                        <p class="mt-2 text-3xl font-semibold text-emerald-700">{{ $this->acquisitionStats['completed_ocr'] }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/80 bg-white/90 p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Scan inbox</p>
                        <p class="mt-2 text-3xl font-semibold text-amber-700">{{ $this->acquisitionStats['scan_inbox'] }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/80 bg-white/90 p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">OCR en cours</p>
                        <p class="mt-2 text-3xl font-semibold text-sky-700">{{ $this->acquisitionStats['processing_ocr'] }}</p>
                    </div>
                </div>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(360px,0.95fr)]">
            <div class="space-y-6">
                <form wire:submit="save" class="space-y-6">
                    {{ $this->form }}

                    <div class="flex justify-end">
                        <x-filament::button
                            type="submit"
                            size="lg"
                            icon="heroicon-o-cloud-arrow-up"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove>Envoyer vers la GED</span>
                            <span wire:loading>Importation…</span>
                        </x-filament::button>
                    </div>
                </form>

                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Panier d'acquisition</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                Prévisualisation fonctionnelle avant transfert: volume, type détecté, disponibilité OCR et dossier cible suggéré.
                            </p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                            {{ count($this->acquisitionBasket) }} fichier(s)
                        </span>
                    </div>

                    <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-slate-600">
                                <tr>
                                    <th class="px-4 py-3 font-semibold">Document</th>
                                    <th class="px-4 py-3 font-semibold">Type détecté</th>
                                    <th class="px-4 py-3 font-semibold">OCR</th>
                                    <th class="px-4 py-3 font-semibold">Classement suggéré</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($this->acquisitionBasket as $item)
                                    <tr>
                                        <td class="px-4 py-3 align-top">
                                            <div class="font-medium text-slate-900">{{ $item['name'] }}</div>
                                            <div class="mt-1 text-xs text-slate-500">{{ $item['extension'] }} · {{ $item['size_human'] }} · {{ $item['mime_type'] }}</div>
                                        </td>
                                        <td class="px-4 py-3 align-top">
                                            <span class="inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700">
                                                {{ $item['detected_type'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 align-top">
                                            @if ($item['ocr_ready'])
                                                <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Disponible</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Limité</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 align-top text-slate-600">
                                            @if ($item['target_dossier'])
                                                <div class="font-medium text-slate-900">{{ $item['target_dossier'] }}</div>
                                                <div class="mt-1 text-xs text-slate-500">{{ $item['target_dossier_path'] }}</div>
                                                @if ($item['target_dossier_documents'])
                                                    <div class="mt-1 text-xs font-semibold text-emerald-700">{{ $item['target_dossier_documents'] }} doc. déjà classé(s)</div>
                                                @endif
                                            @else
                                                Aucune suggestion
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-500">
                                            Ajoutez des fichiers pour alimenter le panier, déclencher la détection automatique et préparer le transfert vers la GED.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div class="space-y-6">
                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Scanner par lot</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Utilisez un scanner réseau ou multifonction configuré vers le dossier surveillé pour industrialiser l'acquisition de gros volumes.
                    </p>

                    <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Dossier de scan</p>
                        <p class="mt-2 break-all text-sm font-medium text-slate-900">{{ $this->scanInboxPath }}</p>
                        <p class="mt-2 text-sm text-slate-600">{{ $this->scanInboxCount }} fichier(s) détecté(s) en attente de traitement.</p>
                    </div>

                    <div class="mt-4 space-y-3">
                        @foreach ($this->scannerConnectionHints as $hint)
                            <div class="flex items-start gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                <span class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-900 text-xs font-semibold text-white">{{ $loop->iteration }}</span>
                                <span>{{ $hint }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Aperçu scan inbox</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                Lot scanner prêt à être aspiré vers la GED avec détection de type avant import.
                            </p>
                        </div>
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">{{ $this->scanInboxCount }}</span>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse ($this->scanInboxFiles as $file)
                            <div class="rounded-2xl border border-slate-200 px-4 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="font-medium text-slate-900">{{ $file['name'] }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $file['extension'] }} · {{ $file['size'] }}</div>
                                    </div>
                                    <span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700">{{ $file['detected_type'] }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                                Aucun fichier détecté dans le dossier scanner pour le moment.
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
