<x-filament-panels::page>
    <style>
        .sysged-share-page {
            --sysged-doc-text: var(--pluss-sidebar-text, #102a4d);
            --sysged-doc-muted: var(--pluss-sidebar-muted, #244f82);
            --sysged-doc-accent-bg: var(--pluss-sidebar-active-bg, #0f2f5f);
            --sysged-doc-accent-text: var(--pluss-sidebar-active-text, #ffffff);
        }

        .sysged-share-page {
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            border-radius: 14px;
            padding: 10px;
        }

        .sysged-share-page,
        .sysged-share-page p,
        .sysged-share-page h3,
        .sysged-share-page th,
        .sysged-share-page td,
        .sysged-share-page span,
        .sysged-share-page a {
            color: var(--sysged-doc-text) !important;
        }

        .dark .sysged-share-page,
        .dark .sysged-share-page p,
        .dark .sysged-share-page h3,
        .dark .sysged-share-page th,
        .dark .sysged-share-page td,
        .dark .sysged-share-page span,
        .dark .sysged-share-page a {
            color: var(--sysged-doc-accent-text) !important;
        }

        .sysged-share-page .text-gray-700,
        .sysged-share-page .text-gray-900,
        .sysged-share-page .dark\:text-white,
        .sysged-share-page .dark\:text-gray-200,
        .sysged-share-page .dark\:text-gray-300 {
            color: var(--sysged-doc-text) !important;
        }

        .sysged-share-page .text-sm {
            color: var(--sysged-doc-muted) !important;
        }

        .sysged-share-page .text-2xl,
        .sysged-share-page .font-semibold,
        .sysged-share-page .font-medium {
            color: var(--sysged-doc-text) !important;
        }

        .dark .sysged-share-page {
            background: linear-gradient(180deg, #111827 0%, #1f2937 100%);
        }

        .sysged-share-page .sys-card {
            background: #ffffff !important;
            border: 1px solid #d1d5db !important;
        }

        .dark .sysged-share-page .sys-card {
            background: #1f2937 !important;
            border: 1px solid #4b5563 !important;
        }

        .sysged-share-page .sys-table {
            background: transparent !important;
        }

        .dark .sysged-share-page .sys-table thead {
            background: rgba(255, 255, 255, 0.03) !important;
        }

        .dark .sysged-share-page .sys-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.04) !important;
        }

        .sysged-share-page .sys-table th {
            font-weight: 700 !important;
        }

        /* Preset: Clair permanent (force light surface/text, even in dark mode) */
        .sysged-share-page.variant-light-force {
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%) !important;
        }

        .sysged-share-page.variant-light-force,
        .sysged-share-page.variant-light-force p,
        .sysged-share-page.variant-light-force h3,
        .sysged-share-page.variant-light-force th,
        .sysged-share-page.variant-light-force td,
        .sysged-share-page.variant-light-force span,
        .sysged-share-page.variant-light-force a,
        .dark .sysged-share-page.variant-light-force,
        .dark .sysged-share-page.variant-light-force p,
        .dark .sysged-share-page.variant-light-force h3,
        .dark .sysged-share-page.variant-light-force th,
        .dark .sysged-share-page.variant-light-force td,
        .dark .sysged-share-page.variant-light-force span,
        .dark .sysged-share-page.variant-light-force a {
            color: #111827 !important;
        }

        .sysged-share-page.variant-light-force .sys-card,
        .dark .sysged-share-page.variant-light-force .sys-card {
            background: #ffffff !important;
            border: 1px solid #d1d5db !important;
        }

        .sysged-share-page.variant-light-force .sys-table thead,
        .dark .sysged-share-page.variant-light-force .sys-table thead {
            background: #f9fafb !important;
        }

        .sysged-share-page.variant-light-force .sys-table tbody tr:hover,
        .dark .sysged-share-page.variant-light-force .sys-table tbody tr:hover {
            background: #f3f4f6 !important;
        }

        /* Preset: Sombre doux */
        .dark .sysged-share-page.variant-soft-dark {
            background: linear-gradient(180deg, #1f2937 0%, #243244 100%) !important;
        }

        .dark .sysged-share-page.variant-soft-dark {
            --sysged-doc-text: var(--sysged-doc-accent-text);
            --sysged-doc-muted: rgba(255, 255, 255, 0.85);
        }

        .dark .sysged-share-page.variant-soft-dark .sys-card {
            background: #273447 !important;
            border: 1px solid #4b5563 !important;
        }

        .dark .sysged-share-page.variant-soft-dark .sys-table thead {
            background: rgba(255, 255, 255, 0.04) !important;
        }

        .dark .sysged-share-page.variant-soft-dark .sys-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.05) !important;
        }

        .sysged-share-page .sys-open-btn {
            background: var(--sysged-doc-accent-bg) !important;
            color: var(--sysged-doc-accent-text) !important;
            border: 1px solid color-mix(in srgb, var(--sysged-doc-accent-bg) 70%, white 30%) !important;
        }

        .sysged-share-page .sys-open-btn:hover {
            filter: brightness(0.95);
        }
    </style>

    <div class="space-y-4">
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Theme d'affichage:</span>
        <button
            type="button"
            wire:click="setThemePreset('light-force')"
            @class([
                'rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold transition-colors',
                'bg-gray-900 text-white dark:bg-white dark:text-gray-900' => $themePreset === 'light-force',
                'bg-white text-gray-700 dark:bg-gray-800 dark:text-gray-200' => $themePreset !== 'light-force',
            ])
        >
            Clair permanent
        </button>
        <button
            type="button"
            wire:click="setThemePreset('soft-dark')"
            @class([
                'rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold transition-colors',
                'bg-gray-900 text-white dark:bg-white dark:text-gray-900' => $themePreset === 'soft-dark',
                'bg-white text-gray-700 dark:bg-gray-800 dark:text-gray-200' => $themePreset !== 'soft-dark',
            ])
        >
            Sombre doux
        </button>
    </div>

    <div @class([
        'sysged-share-page',
        'variant-light-force' => $themePreset === 'light-force',
        'variant-soft-dark' => $themePreset === 'soft-dark',
    ])>
    @php($stats = $this->getShareStats())

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="sys-card rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Liens actifs (partages recus)</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($stats['active_shares']) }}</p>
        </div>
        <div class="sys-card rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Partages externes (recus)</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($stats['external_shares']) }}</p>
        </div>
        <div class="sys-card rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Presences en ligne (docs partages, 5 min)</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($stats['active_presence']) }}</p>
        </div>
        <div class="sys-card rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Actions aujourd'hui (docs partages)</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($stats['actions_today']) }}</p>
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-amber-400 bg-amber-50 p-4 dark:border-amber-600 dark:bg-amber-950/30">
        <h3 class="text-base font-semibold text-amber-900 dark:text-amber-200">Perimetre des droits</h3>
        <p class="mt-1 text-sm text-amber-900 dark:text-amber-100">
            Cet espace est filtre sur les documents qui vous ont ete partages.
            Vous ne voyez pas les elements hors de votre perimetre de partage.
        </p>
        @if($this->canOpenDocumentsIndex())
        <div class="mt-3">
            <a
                href="{{ $this->getDocumentsUrl() }}"
                class="inline-flex items-center gap-2 rounded-lg bg-amber-700 px-4 py-2 text-sm font-medium text-white hover:bg-amber-800"
            >
                Ouvrir Documents GED
            </a>
        </div>
        @endif
    </div>

    <div class="sys-card mt-6 rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Documents partages avec moi</h3>
        <div class="mt-3 overflow-x-auto">
            <table class="sys-table min-w-full divide-y divide-gray-300 text-sm dark:divide-gray-700">
                <thead>
                    <tr class="text-left text-gray-700 dark:text-gray-200">
                        <th class="px-2 py-2">Reference</th>
                        <th class="px-2 py-2">Titre</th>
                        <th class="px-2 py-2">Partage par</th>
                        <th class="px-2 py-2">Acces</th>
                        <th class="px-2 py-2">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($this->getMyShares() as $share)
                        <tr>
                            <td class="px-2 py-2 text-gray-900 dark:text-white">{{ $share->document?->reference_doc ?? '—' }}</td>
                            <td class="px-2 py-2 text-gray-900 dark:text-white">{{ $share->document?->titre ?? 'Document' }}</td>
                            <td class="px-2 py-2 text-gray-900 dark:text-white">{{ $share->sharedBy?->name ?? 'Systeme' }}</td>
                            <td class="px-2 py-2 text-gray-900 dark:text-white">
                                @php($access = [])
                                @if($share->can_view) @php($access[] = 'lecture') @endif
                                @if($share->can_download) @php($access[] = 'telechargement') @endif
                                @if($share->can_comment) @php($access[] = 'commentaire') @endif
                                @if($share->can_edit) @php($access[] = 'edition') @endif
                                {{ empty($access) ? '—' : implode(', ', $access) }}
                            </td>
                            <td class="px-2 py-2">
                                @if($share->document_id)
                                    <a
                                        href="{{ $this->getDocumentViewUrl($share->document_id) }}"
                                        class="sys-open-btn inline-flex items-center rounded-md px-3 py-1.5 text-xs font-medium"
                                    >
                                        Ouvrir
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-2 py-3 text-gray-700 dark:text-gray-300" colspan="5">Aucun document ne vous a encore ete partage.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="sys-card mt-6 rounded-xl border border-gray-300 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Journal recent SYSGED Share</h3>
        <div class="mt-3 overflow-x-auto">
            <table class="sys-table min-w-full divide-y divide-gray-300 text-sm dark:divide-gray-700">
                <thead>
                    <tr class="text-left text-gray-700 dark:text-gray-200">
                        <th class="px-2 py-2">Date</th>
                        <th class="px-2 py-2">Action</th>
                        <th class="px-2 py-2">Acteur</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($this->getRecentShareEvents() as $event)
                        <tr>
                            <td class="px-2 py-2 whitespace-nowrap text-gray-900 dark:text-white">{{ optional($event->created_at)->format('d/m/Y H:i') }}</td>
                            <td class="px-2 py-2 text-gray-900 dark:text-white">{{ $event->action }}</td>
                            <td class="px-2 py-2 text-gray-900 dark:text-white">{{ $event->actor?->name ?? 'Systeme' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-2 py-3 text-gray-700 dark:text-gray-300" colspan="3">Aucune action SYSGED Share enregistree.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    </div>
    </div>
</x-filament-panels::page>
