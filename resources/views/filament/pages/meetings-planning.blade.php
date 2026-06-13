@php
    $matrix      = $this->getMatrix();
    $year        = $this->selectedYear;
    $jeeStatuses = \App\Models\Meeting::JEE_STATUSES;

    $hexBg = [
        'not_done'    => '#ef4444',
        'launched'    => '#f97316',
        'in_progress' => '#facc15',
        'completed'   => '#22c55e',
    ];
    $hexText = [
        'not_done'    => '#ffffff',
        'launched'    => '#ffffff',
        'in_progress' => '#1f2937',
        'completed'   => '#ffffff',
    ];
    $hexTextLight = [
        'not_done'    => '#dc2626',
        'launched'    => '#ea580c',
        'in_progress' => '#ca8a04',
        'completed'   => '#16a34a',
    ];
    $jeeIcons = [
        'not_done'    => '✗',
        'launched'    => '▶',
        'in_progress' => '⚙',
        'completed'   => '✓',
    ];
    $jeeLabelsShort = [
        'not_done'    => 'Pas fait',
        'launched'    => 'Lancé',
        'in_progress' => 'En cours',
        'completed'   => 'Réalisé',
    ];

    $allMeetings = \App\Models\Meeting::where('planning_year', $year)->whereNotNull('committee_type')->get();
    $total       = $allMeetings->count();
    $stats       = $allMeetings->groupBy('jee_status')->map->count();

    // Taux d'exécution basé sur les indicateurs saisis par ligne
    $totalPlanned = collect($matrix)->flatMap(fn($g) => $g['rows'])->sum('target_count');
    $totalPlanned = $totalPlanned > 0 ? $totalPlanned : $total;
    $execRate     = $totalPlanned > 0 ? round($stats->get('completed', 0) / $totalPlanned * 100) : 0;
@endphp

<x-filament-panels::page>

    {{-- =====================================================================
         1. KPI CARDS
    ===================================================================== --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-5">
        @foreach ($jeeStatuses as $key => $label)
            @php
                $cnt  = $stats->get($key, 0);
                $pct  = $total > 0 ? round($cnt / $total * 100) : 0;
                $bg   = $hexBg[$key];
                $txt  = $hexText[$key];
                $txtL = $hexTextLight[$key];
                $icon = $jeeIcons[$key];
            @endphp
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow border border-gray-100 dark:border-gray-800 overflow-hidden flex flex-col">
                <div class="h-2 rounded-t-2xl" :style="'background:{{ $bg }}'"></div>
                <div class="p-5 flex-1 flex flex-col gap-3">
                    <div class="flex items-start justify-between">
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center text-xl font-black shadow-sm"
                             :style="'background:{{ $bg }};color:{{ $txt }}'">
                            {!! $icon !!}
                        </div>
                        <span class="text-4xl font-black text-gray-900 dark:text-white leading-none">{{ $cnt }}</span>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest leading-tight"
                           :style="'color:{{ $txtL }}'">{{ $label }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">sur {{ $total }} planifiées</p>
                    </div>
                    <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                        <div class="h-2 rounded-full transition-all duration-700"
                             :style="'width:{{ $pct }}%;background:{{ $bg }}'"></div>
                    </div>
                    <p class="text-right text-sm font-bold" :style="'color:{{ $txtL }}'">{{ $pct }} %</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- =====================================================================
         2. BARRE DE PROGRESSION GLOBALE + LÉGENDE
    ===================================================================== --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow border border-gray-200 dark:border-gray-700 p-5 mb-5">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h4 class="font-bold text-gray-800 dark:text-gray-100">
                Progression globale — {{ $year }}
            </h4>
            <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                {{ $stats->get('completed', 0) }} réalisées / {{ $totalPlanned }} prévues — Taux d'exécution : <strong>{{ $execRate }}%</strong>
            </span>
        </div>

        @if ($total > 0)
            <div class="w-full h-9 rounded-xl overflow-hidden flex mb-4 shadow-inner">
                @foreach (['completed','in_progress','launched','not_done'] as $k)
                    @php $w = $total > 0 ? round($stats->get($k, 0) / $total * 100) : 0; @endphp
                    @if ($w > 0)
                        <div class="h-9 flex items-center justify-center transition-all duration-700"
                             :style="'width:{{ $w }}%;background:{{ $hexBg[$k] }}'"
                             title="{{ $jeeStatuses[$k] }}: {{ $w }}%">
                            <span class="text-xs font-black" :style="'color:{{ $hexText[$k] }}'">
                                @if ($w >= 10) {{ $w }}% @endif
                            </span>
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <div class="w-full h-9 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-4">
                <span class="text-xs text-gray-400 italic">Cliquez sur "Initialiser l'année" pour démarrer le suivi</span>
            </div>
        @endif

        <div class="flex flex-wrap gap-2">
            @foreach ($jeeStatuses as $key => $label)
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold shadow-sm"
                     :style="'background:{{ $hexBg[$key] }};color:{{ $hexText[$key] }}'">
                    {!! $jeeIcons[$key] !!} {{ $label }}
                    <span class="opacity-80">({{ $stats->get($key, 0) }})</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- =====================================================================
         3. TABLEAU DE PLANNING (format document)
    ===================================================================== --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-xs" style="min-width:920px">
                <thead>
                    {{-- Titre --}}
                    <tr>
                        <th colspan="18"
                            class="py-3 px-4 text-center font-extrabold uppercase tracking-wide border border-gray-400 text-sm"
                            style="background:#dbeafe;color:#1e3a5f">
                            PROGRAMME DES RÉUNIONS STATUTAIRES DES ORGANES DE LA PLUSS POUR {{ $year }}
                        </th>
                    </tr>
                    {{-- En-têtes colonnes (ligne 1/2) --}}
                    <tr style="background:#f5c518;color:#1a1a1a">
                        <th rowspan="2"
                            class="border border-gray-500 px-1 py-2 font-extrabold uppercase text-center align-middle"
                            style="width:32px">N°</th>
                        <th rowspan="2"
                            class="border border-gray-500 px-3 py-2 font-extrabold uppercase text-center align-middle"
                            style="min-width:175px">ACTIVITÉS</th>
                        <th colspan="12"
                            class="border border-gray-500 px-2 py-1 font-extrabold uppercase text-center">
                            CHRONOGRAMME
                        </th>
                        <th rowspan="2"
                            class="border border-gray-500 px-2 py-2 font-extrabold uppercase text-center align-middle leading-tight"
                            style="min-width:130px">ORGANE<br>RESPONSABLE</th>
                        <th rowspan="2"
                            class="border border-gray-500 px-2 py-2 font-extrabold uppercase text-center align-middle"
                            style="min-width:100px">INDICATEURS</th>
                        <th rowspan="2"
                            class="border border-gray-500 px-2 py-2 font-extrabold uppercase text-center align-middle"
                            style="min-width:100px">VÉRIFICATION</th>
                        <th rowspan="2"
                            class="border border-gray-500 px-2 py-2 font-extrabold uppercase text-center align-middle"
                            style="min-width:120px">COMMENTAIRES</th>
                    </tr>
                    {{-- Lettres des mois (ligne 2/2) --}}
                    <tr style="background:#f5c518;color:#1a1a1a">
                        @foreach(['J','F','M','A','M','J','J','A','S','O','N','D'] as $ml)
                            <th class="border border-gray-500 py-1 font-extrabold text-center align-middle"
                                style="width:30px">{{ $ml }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @php $rowNum = 1; @endphp
                    @foreach ($matrix as $group)
                        {{-- En-tête de groupe --}}
                        <tr>
                            <td colspan="18"
                                class="py-2 px-4 text-center font-extrabold uppercase tracking-widest text-xs border border-gray-500 text-white"
                                style="background:#334155">
                                {{ $group['group_label'] }}
                            </td>
                        </tr>

                        @foreach ($group['rows'] as $row)
                            @php
                                $colsType = $row['cols_type'];
                                $ctType   = match (true) {
                                    str_starts_with($row['key'], 'comite_veille')    => 'comite_veille',
                                    str_starts_with($row['key'], 'comite_technique') => 'comite_technique',
                                    str_starts_with($row['key'], 'secretariat')      => 'secretariat_technique',
                                    str_starts_with($row['key'], 'gtt')              => 'gtt',
                                    default                                          => 'other',
                                };
                                $gttId   = $row['gtt_id'] ?? null;
                                $colspan = match ($colsType) {
                                    'semesters' => 6,
                                    'quarters'  => 3,
                                    default     => 1,
                                };
                                $organe = match ($ctType) {
                                    'comite_veille'         => 'Comité de Veille',
                                    'comite_technique'      => 'Comité Technique',
                                    'secretariat_technique' => 'Secr. Tech. Multisectoriel',
                                    'gtt'                   => $row['label'],
                                    default                 => '—',
                                };
                                // Couleurs par type de comité
                                [$rowBg, $rowBorder, $rowText, $rowBadgeBg, $rowBadgeText, $rowBadge] = match ($ctType) {
                                    'comite_veille'         => ['#f3e8ff', '#7c3aed', '#5b21b6', '#7c3aed', '#ffffff', 'CV'],
                                    'comite_technique'      => ['#dbeafe', '#2563eb', '#1d4ed8', '#2563eb', '#ffffff', 'CT'],
                                    'secretariat_technique' => ['#ccfbf1', '#0d9488', '#0f766e', '#0d9488', '#ffffff', 'STM'],
                                    'gtt'                   => ['#fef3c7', '#d97706', '#92400e', '#d97706', '#ffffff', 'GTT'],
                                    default                 => ['#f9fafb', '#6b7280', '#374151', '#6b7280', '#ffffff', '—'],
                                };
                            @endphp

                            {{-- Sous-titre comité (coloré par type) --}}
                            <tr>
                                <td colspan="18"
                                    class="border border-gray-300 dark:border-gray-600"
                                    :style="'background:{{ $rowBg }};border-left:5px solid {{ $rowBorder }};padding:0'">
                                    <div class="flex items-center gap-3 px-4 py-2">
                                        <span class="inline-flex items-center justify-center rounded-md px-2 py-0.5 text-[11px] font-extrabold tracking-wider shrink-0"
                                              :style="'background:{{ $rowBadgeBg }};color:{{ $rowBadgeText }}'">
                                            {{ $rowBadge }}
                                        </span>
                                        <span class="font-bold text-sm"
                                              :style="'color:{{ $rowText }}'">
                                            {{ $row['label'] }}
                                        </span>
                                        <span class="text-[10px] italic font-normal ml-1"
                                              :style="'color:{{ $rowBorder }};opacity:0.75'">
                                            — {{ $row['sub_label'] ?? '' }}
                                        </span>
                                    </div>
                                </td>
                            </tr>

                            {{-- Ligne de données --}}
                            <tr class="transition-colors" style="background:#fffdf0" onmouseover="this.style.background='#fefce8'" onmouseout="this.style.background='#fffdf0'">
                                <td class="border border-gray-300 text-center text-xs text-gray-500 py-1 px-1 align-middle">
                                    {{ $rowNum++ }}
                                </td>
                                <td class="border border-gray-300 px-3 py-2 text-xs font-medium align-middle"
                                    :style="'border-left:4px solid {{ $rowBorder }};color:{{ $rowText }}'">
                                    {{ $row['sub_label'] ?? 'Tenue des réunions statutaires' }}
                                </td>

                                @foreach ($row['cells'] as $cell)
                                    @php
                                        $meeting   = $cell['meeting'];
                                        $jeeStatus = $meeting?->jee_status ?? 'not_done';
                                        $cellBg    = $hexBg[$jeeStatus];
                                        $cellTxt   = $hexText[$jeeStatus];
                                        $cellIcon  = $jeeIcons[$jeeStatus];
                                    @endphp
                                    <td colspan="{{ $colspan }}"
                                        class="border border-gray-300 p-0 align-middle"
                                        style="height:44px">
                                        <div x-data="{ open: false }" class="relative h-full">
                                            <button
                                                @click="open = !open"
                                                class="w-full h-full flex flex-col items-center justify-center font-bold
                                                       cursor-pointer select-none transition-all duration-150
                                                       hover:opacity-80 active:scale-95 focus:outline-none"
                                                :style="'background:{{ $cellBg }};color:{{ $cellTxt }};min-height:44px'"
                                                title="{{ $cell['period_label'] }} — {{ $jeeStatuses[$jeeStatus] }}"
                                            >
                                                <span class="font-black text-[11px] leading-none">{{ $cell['period'] }}</span>
                                                <span class="text-[10px] leading-none mt-1 opacity-90">{!! $cellIcon !!}</span>
                                                @if ($meeting?->planned_date)
                                                    <span class="text-[9px] leading-none mt-0.5 opacity-80 font-semibold">📅 {{ $meeting->planned_date->format('d/m') }}</span>
                                                @endif
                                            </button>

                                            {{-- Sélecteur de statut --}}
                                            <div
                                                x-show="open"
                                                x-cloak
                                                x-transition:enter="transition ease-out duration-100"
                                                x-transition:enter-start="opacity-0 scale-95"
                                                x-transition:enter-end="opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-75"
                                                x-transition:leave-start="opacity-100 scale-100"
                                                x-transition:leave-end="opacity-0 scale-95"
                                                @click.outside="open = false"
                                                @keydown.escape.window="open = false"
                                                class="absolute z-50 bottom-full mb-1 left-1/2 -translate-x-1/2
                                                       w-64 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700
                                                       bg-white dark:bg-gray-900 overflow-hidden"
                                            >
                                                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                                                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">
                                                        {{ $cell['period_label'] }} · {{ $year }}
                                                    </p>
                                                    <p class="text-sm font-bold text-gray-800 dark:text-gray-100 mt-0.5 truncate">
                                                        {{ $row['label'] }}
                                                    </p>
                                                </div>

                                                @foreach ($jeeStatuses as $sKey => $sLabel)
                                                    @php $isActive = $jeeStatus === $sKey; @endphp
                                                    <button
                                                        wire:click="updateJeeStatus('{{ $ctType }}', '{{ $cell['period'] }}', '{{ $sKey }}', {{ $gttId ?? 'null' }})"
                                                        @click="open = false"
                                                        class="flex items-center gap-3 w-full px-4 py-3 text-left transition-colors {{ $isActive ? '' : 'hover:bg-gray-50 dark:hover:bg-gray-800' }}"
                                                        @if ($isActive)
                                                            :style="'background:{{ $hexBg[$sKey] }};color:{{ $hexText[$sKey] }}'"
                                                        @endif
                                                    >
                                                        <span class="w-7 h-7 rounded-lg flex items-center justify-center text-sm font-black shadow-sm shrink-0"
                                                              :style="'background:{{ $hexBg[$sKey] }};color:{{ $hexText[$sKey] }}'">
                                                            {!! $jeeIcons[$sKey] !!}
                                                        </span>
                                                        <span class="text-sm font-semibold flex-1 {{ $isActive ? '' : 'text-gray-700 dark:text-gray-300' }}">
                                                            {{ $sLabel }}
                                                        </span>
                                                        @if ($isActive)
                                                            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                            </svg>
                                                        @endif
                                                    </button>
                                                @endforeach

                                                {{-- Date de la réunion --}}
                                                <div class="border-t border-gray-100 dark:border-gray-700 px-4 py-3">
                                                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2">📅 Date de la réunion</p>
                                                    <input
                                                        type="date"
                                                        value="{{ $meeting?->planned_date?->format('Y-m-d') ?? '' }}"
                                                        @change="$wire.updatePlannedDate('{{ $ctType }}', '{{ $cell['period'] }}', $event.target.value, {{ $gttId ?? 'null' }})"
                                                        class="w-full rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 px-2 py-1.5 text-xs text-gray-700 focus:outline-none focus:ring-1 focus:ring-blue-300"
                                                        placeholder="jj/mm/aaaa"
                                                    >
                                                    @if ($meeting?->planned_date)
                                                        <button
                                                            wire:click="updatePlannedDate('{{ $ctType }}', '{{ $cell['period'] }}', '', {{ $gttId ?? 'null' }})"
                                                            class="mt-1 text-[10px] text-gray-400 hover:text-red-500 hover:underline"
                                                        >✕ Effacer la date</button>
                                                    @endif
                                                </div>

                                                {{-- Section vérification : TDR + Rapport --}}
                                                <div class="border-t border-gray-100 dark:border-gray-700 px-4 py-3"
                                                     x-data="{
                                                         uploading: '',
                                                         uploadError: '',
                                                         hasTdr: {{ $meeting?->tdr_path ? 'true' : 'false' }},
                                                         hasRapport: {{ $meeting?->rapport_path ? 'true' : 'false' }},
                                                         async uploadDoc(event, docType) {
                                                             const file = event.target.files[0];
                                                             if (!file) return;
                                                             this.uploading = docType;
                                                             this.uploadError = '';
                                                             const fd = new FormData();
                                                             fd.append('document', file);
                                                             fd.append('doc_type', docType);
                                                             fd.append('committee_type', '{{ $ctType }}');
                                                             fd.append('period', '{{ $cell['period'] }}');
                                                             fd.append('year', '{{ $year }}');
                                                             fd.append('gtt_id', '{{ $gttId ?? '' }}');
                                                             fd.append('_token', document.head.querySelector('meta[name=csrf-token]').content);
                                                             try {
                                                                 const r = await fetch('{{ route('planning.upload') }}', { method: 'POST', body: fd });
                                                                 if (!r.ok) {
                                                                     const txt = await r.text();
                                                                     this.uploadError = 'Erreur ' + r.status;
                                                                     console.error('Upload error', r.status, txt);
                                                                 } else {
                                                                     const d = await r.json();
                                                                     if (d.success) {
                                                                         if (docType === 'tdr') this.hasTdr = true;
                                                                         else this.hasRapport = true;
                                                                         await $wire.$refresh();
                                                                     } else {
                                                                         this.uploadError = d.error ?? 'Échec';
                                                                     }
                                                                 }
                                                             } catch(e) {
                                                                 this.uploadError = 'Erreur réseau';
                                                                 console.error('Upload exception', e);
                                                             } finally {
                                                                 this.uploading = '';
                                                                 event.target.value = '';
                                                             }
                                                         }
                                                     }">
                                                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2">Vérification</p>
                                                    <template x-if="uploadError">
                                                        <p class="text-[10px] text-red-600 font-semibold mb-1" x-text="uploadError"></p>
                                                    </template>
                                                    <div class="flex gap-2">
                                                        {{-- TDR --}}
                                                        <label class="relative flex-1 cursor-pointer">
                                                            <input type="file" class="absolute inset-0 opacity-0 cursor-pointer" accept=".pdf,.doc,.docx"
                                                                   @change="uploadDoc($event, 'tdr')">
                                                            <span class="flex items-center justify-center gap-1 rounded-lg px-2 py-1.5 text-[11px] font-bold w-full transition-colors"
                                                                  :class="hasTdr
                                                                      ? 'bg-orange-100 text-orange-700 border border-orange-300'
                                                                      : 'bg-gray-100 text-gray-600 border border-gray-200 hover:bg-gray-200'">
                                                                <template x-if="uploading === 'tdr'">
                                                                    <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                                                </template>
                                                                <template x-if="uploading !== 'tdr'">
                                                                    <span x-text="hasTdr ? '📄 TDR ✓' : '⬆ TDR'"></span>
                                                                </template>
                                                            </span>
                                                        </label>
                                                        {{-- Rapport --}}
                                                        <label class="relative flex-1 cursor-pointer">
                                                            <input type="file" class="absolute inset-0 opacity-0 cursor-pointer" accept=".pdf,.doc,.docx"
                                                                   @change="uploadDoc($event, 'rapport')">
                                                            <span class="flex items-center justify-center gap-1 rounded-lg px-2 py-1.5 text-[11px] font-bold w-full transition-colors"
                                                                  :class="hasRapport
                                                                      ? 'bg-green-100 text-green-700 border border-green-300'
                                                                      : 'bg-gray-100 text-gray-600 border border-gray-200 hover:bg-gray-200'">
                                                                <template x-if="uploading === 'rapport'">
                                                                    <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                                                </template>
                                                                <template x-if="uploading !== 'rapport'">
                                                                    <span x-text="hasRapport ? '📑 Rapport ✓' : '⬆ Rapport'"></span>
                                                                </template>
                                                            </span>
                                                        </label>
                                                    </div>
                                                    @if ($meeting?->tdr_path)
                                                        <a href="{{ route('planning.download', [$meeting->id, 'tdr']) }}"
                                                           target="_blank"
                                                           class="block mt-1.5 text-[10px] text-orange-600 hover:underline truncate">
                                                            ↗ Télécharger le TDR
                                                        </a>
                                                    @endif
                                                    @if ($meeting?->rapport_path)
                                                        <a href="{{ route('planning.download', [$meeting->id, 'rapport']) }}"
                                                           target="_blank"
                                                           class="block mt-0.5 text-[10px] text-green-600 hover:underline truncate">
                                                            ↗ Télécharger le Rapport
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                @endforeach

                                <td class="border border-gray-300 px-2 py-2 text-xs text-gray-600 dark:text-gray-400 text-center align-middle">
                                    {{ $organe }}
                                </td>

                                {{-- INDICATEURS : nombre de réunions prévues dans l'année --}}
                                <td class="border border-gray-300 px-2 py-2 align-middle text-center" style="min-width:100px">
                                    @php
                                        $indCtType = $ctType;
                                        $indGttId  = $gttId;
                                    @endphp
                                    <input
                                        type="number"
                                        min="1" max="52"
                                        value="{{ $row['target_count'] }}"
                                        class="w-16 text-center text-xs border border-gray-300 rounded px-1 py-1 focus:ring-1 focus:ring-blue-400 focus:outline-none bg-white dark:bg-gray-800 dark:border-gray-600"
                                        title="Nombre de réunions prévues pour l'année"
                                        x-data="{}"
                                        @change="$wire.updateTargetCount('{{ $indCtType }}', {{ $indGttId ?? 'null' }}, parseInt($event.target.value))"
                                    >
                                    <p class="text-[9px] text-gray-400 mt-0.5 leading-none">réunions/an</p>
                                </td>

                                {{-- VÉRIFICATION : résumé des documents chargés pour cette ligne --}}
                                @php
                                    $tdrCount     = collect($row['cells'])->filter(fn($c) => $c['meeting']?->tdr_path)->count();
                                    $rapportCount = collect($row['cells'])->filter(fn($c) => $c['meeting']?->rapport_path)->count();
                                    $cellTotal    = count($row['cells']);
                                @endphp
                                <td class="border border-gray-300 px-2 py-2 align-middle text-center" style="min-width:100px">
                                    <div class="flex flex-col items-center gap-1 text-[10px]">
                                        <span class="{{ $tdrCount > 0 ? 'text-orange-600 font-bold' : 'text-gray-300' }}"
                                              title="TDR chargés">
                                            📄 TDR {{ $tdrCount }}/{{ $cellTotal }}
                                        </span>
                                        <span class="{{ $rapportCount > 0 ? 'text-green-600 font-bold' : 'text-gray-300' }}"
                                              title="Rapports chargés">
                                            📑 Rap. {{ $rapportCount }}/{{ $cellTotal }}
                                        </span>
                                    </div>
                                </td>

                                {{-- COMMENTAIRES : textarea par ligne --}}
                                @php $rowComment = e($row['comment'] ?? ''); @endphp
                                <td class="border border-gray-300 px-2 py-2 align-middle text-center relative" style="min-width:120px">
                                    <div x-data="{
                                             open: false,
                                             comment: '{{ $rowComment }}'
                                         }"
                                         class="relative">
                                        <button
                                            @click="open = !open"
                                            title="Observations"
                                            class="w-7 h-7 rounded-full flex items-center justify-center mx-auto text-sm transition-colors"
                                            :class="comment.trim()
                                                ? 'bg-yellow-100 text-yellow-700 border border-yellow-300'
                                                : 'bg-gray-100 text-gray-400 hover:bg-gray-200'">
                                            💬
                                        </button>
                                        <div
                                            x-show="open"
                                            x-cloak
                                            x-transition
                                            @click.outside="open = false"
                                            @keydown.escape.window="open = false"
                                            class="absolute z-50 right-0 bottom-full mb-2 w-72
                                                   bg-white dark:bg-gray-900 rounded-2xl shadow-2xl
                                                   border border-gray-200 dark:border-gray-700 p-4">
                                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2">
                                                Observations — {{ $row['label'] }}
                                            </p>
                                            <textarea
                                                x-model="comment"
                                                rows="5"
                                                class="w-full text-xs border border-gray-300 dark:border-gray-600 rounded-lg
                                                       px-2 py-1.5 resize-none focus:ring-1 focus:ring-blue-400 focus:outline-none
                                                       bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300"
                                                placeholder="Saisir vos observations..."></textarea>
                                            <div class="flex justify-end gap-2 mt-2">
                                                <button
                                                    @click="open = false"
                                                    class="text-xs text-gray-500 px-3 py-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                                                    Fermer
                                                </button>
                                                <button
                                                    @click="$wire.saveComment('{{ $ctType }}', {{ $gttId ?? 'null' }}, comment); open = false"
                                                    class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-lg font-semibold">
                                                    Sauvegarder
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</x-filament-panels::page>