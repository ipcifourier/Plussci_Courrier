<?php

namespace App\Filament\Pages;

use App\Models\Gtt;
use App\Models\Meeting;
use App\Models\MeetingPlan;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Tableau de programmation et de suivi des réunions (évaluation JEE)
 */
class MeetingsPlanningPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static ?string $slug = 'reunions-planning';

    protected static ?string $navigationLabel = 'Planning & Suivi';

    protected static ?string $title = 'Planning & Suivi des Réunions';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.meetings-planning';

    // ── State ──────────────────────────────────────────────────────────────────
    public int $selectedYear;

    public function mount(): void
    {
        $this->selectedYear = (int) now()->year;
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Agenda';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->hasRole('Super Admin')
            || $user->hasAnyPermission([
                'agenda.viewAny',
                'agenda.view',
                'agenda.meetings.manage',
                'agenda.planning.view',
                'agenda.planning.manage',
            ])
        );
    }

    // ── Year navigation ────────────────────────────────────────────────────────

    public function prevYear(): void
    {
        $this->selectedYear--;
    }

    public function nextYear(): void
    {
        $this->selectedYear++;
    }

    // ── Build the planning matrix ──────────────────────────────────────────────

    /**
     * Returns the full planning matrix for the selected year.
     *
     * Structure: array of "row groups", each group having rows.
     * Every row has a list of "cells" (one per period).
     *
     * @return array<int, array{label: string, rows: array<int, array{key: string, label: string, cells: array<int, array{period: string, period_label: string, meeting: Meeting|null}>}>}>
     */
    public function getMatrix(): array
    {
        $year = $this->selectedYear;

        // Load all planning meetings for this year, eager-load gtt
        $meetings = Meeting::query()
            ->where('planning_year', $year)
            ->whereNotNull('committee_type')
            ->with('gtt')
            ->get()
            ->keyBy(fn (Meeting $m) => $m->committee_type . '|' . ($m->gtt_id ?? '') . '|' . $m->planning_period);

        // Load annual plans (target counts + comments)
        $plans = MeetingPlan::where('planning_year', $year)
            ->get()
            ->keyBy(fn (MeetingPlan $p) => $p->committee_type . '|' . ($p->gtt_id ?? ''));

        $gtts = Gtt::orderBy('name')->get();

        $months = [
            '1' => 'Jan', '2' => 'Fév', '3' => 'Mar',
            '4' => 'Avr', '5' => 'Mai', '6' => 'Jun',
            '7' => 'Jul', '8' => 'Aoû', '9' => 'Sep',
            '10' => 'Oct', '11' => 'Nov', '12' => 'Déc',
        ];

        $matrix = [];

        // ── Groupe 1: Comité de Veille (S1, S2) ───────────────────────────────
        $cvCells = [];
        foreach (['S1' => '1ᵉʳ Semestre', 'S2' => '2ᵉ Semestre'] as $period => $label) {
            $key = 'comite_veille||' . $period;
            $cvCells[] = [
                'period'       => $period,
                'period_label' => $label,
                'meeting'      => $meetings->get($key),
                'colspan'      => 1,
            ];
        }
        $cvPlanKey = 'comite_veille|';
        $matrix[] = [
            'group_label' => 'Instances Nationales',
            'rows'        => [
                [
                    'key'          => 'comite_veille',
                    'label'        => 'Comité de Veille',
                    'sub_label'    => '2 réunions / an (semestrielle)',
                    'badge'        => 'CV',
                    'badge_color'  => 'bg-purple-600',
                    'cols_type'    => 'semesters',
                    'cells'        => $cvCells,
                    'target_count' => $plans->get($cvPlanKey)?->target_count ?? 2,
                    'comment'      => $plans->get($cvPlanKey)?->comment ?? '',
                ],
            ],
        ];

        // ── Groupe 2: Comité Technique (T1-T4) ────────────────────────────────
        $ctCells = [];
        foreach (['T1' => 'T1 (Jan-Mar)', 'T2' => 'T2 (Avr-Jun)', 'T3' => 'T3 (Jul-Sep)', 'T4' => 'T4 (Oct-Déc)'] as $period => $label) {
            $key = 'comite_technique||' . $period;
            $ctCells[] = [
                'period'       => $period,
                'period_label' => $label,
                'meeting'      => $meetings->get($key),
            ];
        }
        $ctPlanKey = 'comite_technique|';
        $matrix[0]['rows'][] = [
            'key'          => 'comite_technique',
            'label'        => 'Comité Technique',
            'sub_label'    => '4 réunions / an (trimestrielle)',
            'badge'        => 'CT',
            'badge_color'  => 'bg-blue-600',
            'cols_type'    => 'quarters',
            'cells'        => $ctCells,
            'target_count' => $plans->get($ctPlanKey)?->target_count ?? 4,
            'comment'      => $plans->get($ctPlanKey)?->comment ?? '',
        ];

        // ── Groupe 3: Secrétariat Technique Multisectoriel (mensuelle) ─────────
        $stmCells = [];
        foreach ($months as $month => $label) {
            $key = 'secretariat_technique||' . $month;
            $stmCells[] = [
                'period'       => $month,
                'period_label' => $label,
                'meeting'      => $meetings->get($key),
            ];
        }
        $stmPlanKey = 'secretariat_technique|';
        $matrix[0]['rows'][] = [
            'key'          => 'secretariat_technique',
            'label'        => 'Secrétariat Technique Multisectoriel',
            'sub_label'    => '12 réunions / an (mensuelle)',
            'badge'        => 'STM',
            'badge_color'  => 'bg-teal-600',
            'cols_type'    => 'months',
            'cells'        => $stmCells,
            'target_count' => $plans->get($stmPlanKey)?->target_count ?? 12,
            'comment'      => $plans->get($stmPlanKey)?->comment ?? '',
        ];

        // ── Groupe 4: GTT (10 groupes, mensuelle) ─────────────────────────────
        $gttRows = [];
        foreach ($gtts as $gtt) {
            $cells = [];
            foreach ($months as $month => $label) {
                $key = 'gtt|' . $gtt->id . '|' . $month;
                $cells[] = [
                    'period'       => $month,
                    'period_label' => $label,
                    'meeting'      => $meetings->get($key),
                    'gtt_id'       => $gtt->id,
                ];
            }
            $gttPlanKey = 'gtt|' . $gtt->id;
            $gttRows[] = [
                'key'          => 'gtt_' . $gtt->id,
                'label'        => $gtt->name,
                'sub_label'    => '12 réunions / an (mensuelle)',
                'badge'        => 'GTT',
                'badge_color'  => 'bg-amber-600',
                'cols_type'    => 'months',
                'cells'        => $cells,
                'gtt_id'       => $gtt->id,
                'target_count' => $plans->get($gttPlanKey)?->target_count ?? 12,
                'comment'      => $plans->get($gttPlanKey)?->comment ?? '',
            ];
        }
        $matrix[] = [
            'group_label' => 'Groupes Techniques de Travail (GTT)',
            'rows'        => $gttRows,
        ];

        return $matrix;
    }

    // ── Update JEE status ──────────────────────────────────────────────────────

    /**
     * Called via wire:click from a cell badge.
     * Cycles through the JEE statuses OR sets directly.
     */
    public function updateJeeStatus(
        string $committeeType,
        string $period,
        string $status,
        ?int $gttId = null,
    ): void {
        $this->checkPlanningAccess();

        $meeting = Meeting::firstOrCreate(
            [
                'committee_type'  => $committeeType,
                'planning_year'   => $this->selectedYear,
                'planning_period' => $period,
                'gtt_id'          => $gttId,
            ],
            [
                'title'      => $this->buildTitle($committeeType, $period, $gttId),
                'starts_at'  => now(),
                'status'     => 'planned',
                'jee_status' => 'not_done',
            ]
        );

        $meeting->update(['jee_status' => $status]);

        Notification::make()
            ->title('Statut mis à jour')
            ->body(Meeting::JEE_STATUSES[$status] ?? $status)
            ->success()
            ->send();
    }

    // ── Save planned date for a cell ─────────────────────────────────────────

    public function updatePlannedDate(
        string $committeeType,
        string $period,
        string $date,
        ?int $gttId = null,
    ): void {
        $this->checkPlanningAccess();

        // Validate date format
        $parsed = null;
        if ($date !== '') {
            try {
                $parsed = \Carbon\Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
            } catch (\Exception $e) {
                return;
            }
        }

        $meeting = Meeting::firstOrCreate(
            [
                'committee_type'  => $committeeType,
                'planning_year'   => $this->selectedYear,
                'planning_period' => $period,
                'gtt_id'          => $gttId,
            ],
            [
                'title'      => $this->buildTitle($committeeType, $period, $gttId),
                'starts_at'  => now(),
                'status'     => 'planned',
                'jee_status' => 'not_done',
            ]
        );

        $meeting->update(['planned_date' => $parsed]);

        Notification::make()
            ->title($parsed ? 'Date enregistrée : ' . $parsed->format('d/m/Y') : 'Date effacée')
            ->success()
            ->send();
    }

    // ── Update annual indicator (target meeting count) ────────────────────────

    public function updateTargetCount(string $committeeType, ?int $gttId, int $count): void
    {
        $this->checkPlanningAccess();

        MeetingPlan::updateOrCreate(
            [
                'planning_year'  => $this->selectedYear,
                'committee_type' => $committeeType,
                'gtt_id'         => $gttId,
            ],
            ['target_count' => max(1, min(52, $count))]
        );
    }

    // ── Save row comment ──────────────────────────────────────────────────────

    public function saveComment(string $committeeType, ?int $gttId, string $comment): void
    {
        $this->checkPlanningAccess();

        MeetingPlan::updateOrCreate(
            [
                'planning_year'  => $this->selectedYear,
                'committee_type' => $committeeType,
                'gtt_id'         => $gttId,
            ],
            ['comment' => mb_substr(trim($comment), 0, 2000)]
        );

        Notification::make()
            ->title('Commentaire enregistré')
            ->success()
            ->send();
    }

    // ── Edit notes/title on a cell ─────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('prevYear')
                ->label('← ' . ($this->selectedYear - 1))
                ->color('gray')
                ->action('prevYear'),

            Action::make('currentYear')
                ->label((string) $this->selectedYear)
                ->color('warning')
                ->disabled(),

            Action::make('nextYear')
                ->label(($this->selectedYear + 1) . ' →')
                ->color('gray')
                ->action('nextYear'),

            Action::make('initYear')
                ->label('Initialiser l\'année')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Initialiser le planning ' . $this->selectedYear)
                ->modalDescription('Ceci va créer toutes les entrées manquantes du planning pour l\'année ' . $this->selectedYear . ' avec le statut "Pas fait". Continuer ?')
                ->action('initializeYear'),

            Action::make('exportExcel')
                ->label('Exporter Excel')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('success')
                ->url(fn (): string => route('planning.export', ['year' => $this->selectedYear]))
                ->openUrlInNewTab(),
        ];
    }

    /**
     * Auto-create all planning entries for the selected year (not_done by default).
     */
    public function initializeYear(): void
    {
        $year  = $this->selectedYear;
        $gtts  = Gtt::all();
        $count = 0;

        $insert = static function (array $attrs) use ($year, &$count): void {
            $exists = Meeting::where($attrs)->where('planning_year', $year)->exists();
            if (! $exists) {
                Meeting::create(array_merge($attrs, [
                    'planning_year' => $year,
                    'status'        => 'planned',
                    'jee_status'    => 'not_done',
                    'starts_at'     => now(),
                ]));
                $count++;
            }
        };

        // CV (S1, S2)
        foreach (['S1', 'S2'] as $p) {
            $insert(['committee_type' => 'comite_veille', 'planning_period' => $p, 'gtt_id' => null,
                'title' => 'Comité de Veille – ' . $p . ' ' . $year]);
        }

        // CT (T1-T4)
        foreach (['T1', 'T2', 'T3', 'T4'] as $p) {
            $insert(['committee_type' => 'comite_technique', 'planning_period' => $p, 'gtt_id' => null,
                'title' => 'Comité Technique – ' . $p . ' ' . $year]);
        }

        // STM (1-12)
        for ($m = 1; $m <= 12; $m++) {
            $insert(['committee_type' => 'secretariat_technique', 'planning_period' => (string) $m, 'gtt_id' => null,
                'title' => 'STM – ' . $this->monthName($m) . ' ' . $year]);
        }

        // GTT (1-12 per group)
        foreach ($gtts as $gtt) {
            for ($m = 1; $m <= 12; $m++) {
                $insert(['committee_type' => 'gtt', 'planning_period' => (string) $m, 'gtt_id' => $gtt->id,
                    'title' => $gtt->name . ' – ' . $this->monthName($m) . ' ' . $year]);
            }
        }

        Notification::make()
            ->title('Planning initialisé')
            ->body($count . ' entrée(s) créée(s) pour ' . $year)
            ->success()
            ->send();
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function buildTitle(string $committeeType, string $period, ?int $gttId): string
    {
        $year = $this->selectedYear;
        return match ($committeeType) {
            'comite_veille'         => 'Comité de Veille – ' . $period . ' ' . $year,
            'comite_technique'      => 'Comité Technique – ' . $period . ' ' . $year,
            'secretariat_technique' => 'STM – ' . $this->monthName((int) $period) . ' ' . $year,
            'gtt'                   => ($gttId ? (Gtt::find($gttId)?->name ?? 'GTT') : 'GTT') . ' – ' . $this->monthName((int) $period) . ' ' . $year,
            default                 => 'Réunion – ' . $period . ' ' . $year,
        };
    }

    private function monthName(int $month): string
    {
        return [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ][$month] ?? '';
    }

    // ── Authorize helper (non-Filament policy) ─────────────────────────────────
    private function checkPlanningAccess(): void
    {
        $user = Auth::user();
        if (
            ! $user instanceof User
            || ! (
                $user->hasRole('Super Admin')
                || $user->hasAnyPermission(['agenda.meetings.manage', 'agenda.planning.manage'])
            )
        ) {
            Notification::make()
                ->title('Action non autorisée')
                ->danger()
                ->send();
            $this->halt();
        }
    }
}
