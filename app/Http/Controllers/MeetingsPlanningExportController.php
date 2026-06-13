<?php

namespace App\Http\Controllers;

use App\Models\Gtt;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MeetingsPlanningExportController extends Controller
{
    // ── JEE colour map (ARGB for PhpSpreadsheet) ──────────────────────────────
    private const ARGB = [
        'not_done'    => ['bg' => 'FFEF4444', 'text' => 'FFFFFFFF'], // red
        'launched'    => ['bg' => 'FFF97316', 'text' => 'FFFFFFFF'], // orange
        'in_progress' => ['bg' => 'FFFACC15', 'text' => 'FF1F2937'], // yellow
        'completed'   => ['bg' => 'FF22C55E', 'text' => 'FFFFFFFF'], // green
    ];

    private const MONTHS = [
        '1' => 'Janvier', '2' => 'Février', '3' => 'Mars', '4' => 'Avril',
        '5' => 'Mai', '6' => 'Juin', '7' => 'Juillet', '8' => 'Août',
        '9' => 'Septembre', '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre',
    ];

    public function __invoke(Request $request, int $year): StreamedResponse
    {
        $user = $request->user() ?? Auth::user();
        abort_unless(
            $user instanceof User && (
                $user->hasRole('Super Admin')
                || $user->hasAnyPermission([
                    'agenda.viewAny', 'agenda.view',
                    'agenda.meetings.manage', 'agenda.planning.view', 'agenda.planning.manage',
                ])
            ),
            403
        );

        $spreadsheet = $this->build($year);
        $filename    = 'planning_reunions_' . $year . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    // ── Build the spreadsheet ─────────────────────────────────────────────────

    private function build(int $year): Spreadsheet
    {
        // Load all meetings for this year
        $meetings = Meeting::where('planning_year', $year)
            ->whereNotNull('committee_type')
            ->with('gtt')
            ->get()
            ->keyBy(fn (Meeting $m) => $m->committee_type . '|' . ($m->gtt_id ?? '') . '|' . $m->planning_period);

        $gtts = Gtt::orderBy('name')->get();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setTitle('Planning Réunions ' . $year)
            ->setCreator('PlussCI')
            ->setSubject('Suivi JEE des réunions ' . $year);

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Planning ' . $year);
        $sheet->getDefaultColumnDimension()->setWidth(20);

        $row = 1;

        // ── Main title ────────────────────────────────────────────────────────
        $sheet->setCellValue('A' . $row, 'PLANNING ET SUIVI DES RÉUNIONS — ' . $year);
        $sheet->mergeCells('A' . $row . ':N' . $row);
        $this->applyStyle($sheet, 'A' . $row . ':N' . $row, [
            'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(36);
        $row++;

        // Subtitle
        $sheet->setCellValue('A' . $row, 'Généré le ' . now()->translatedFormat('d F Y à H:i') . ' — Évaluation JEE');
        $sheet->mergeCells('A' . $row . ':N' . $row);
        $this->applyStyle($sheet, 'A' . $row . ':N' . $row, [
            'font' => ['italic' => true, 'size' => 10, 'color' => ['argb' => 'FF6B7280']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF8FAFC']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(18);
        $row += 2;

        // ── Statistics block ──────────────────────────────────────────────────
        $row = $this->writeStats($sheet, $row, $year);
        $row++;

        // ── Instances Nationales ──────────────────────────────────────────────
        $row = $this->writeSectionHeader($sheet, $row, 'INSTANCES NATIONALES');

        // Comité de Veille
        $row = $this->writeSectionRowHeader($sheet, $row, 'Comité de Veille (CV)', '2 réunions semestrielles');
        $cvPeriods = ['S1' => '1er Semestre (Jan–Jun)', 'S2' => '2ème Semestre (Jul–Déc)'];
        $row       = $this->writePeriodRow($sheet, $row, 'comite_veille', null, $cvPeriods, $meetings);

        // Comité Technique
        $row = $this->writeSectionRowHeader($sheet, $row, 'Comité Technique (CT)', '4 réunions trimestrielles');
        $ctPeriods = ['T1' => 'T1 (Jan–Mar)', 'T2' => 'T2 (Avr–Jun)', 'T3' => 'T3 (Jul–Sep)', 'T4' => 'T4 (Oct–Déc)'];
        $row       = $this->writePeriodRow($sheet, $row, 'comite_technique', null, $ctPeriods, $meetings);

        // STM
        $row = $this->writeSectionRowHeader($sheet, $row, 'Secrétariat Technique Multisectoriel (STM)', '12 réunions mensuelles');
        $row = $this->writePeriodRow($sheet, $row, 'secretariat_technique', null, self::MONTHS, $meetings);
        $row++;

        // ── GTT ───────────────────────────────────────────────────────────────
        $row = $this->writeSectionHeader($sheet, $row, 'GROUPES TECHNIQUES DE TRAVAIL (GTT)');

        foreach ($gtts as $gtt) {
            $row = $this->writeSectionRowHeader($sheet, $row, $gtt->name, '12 réunions mensuelles');
            $row = $this->writePeriodRow($sheet, $row, 'gtt', $gtt->id, self::MONTHS, $meetings);
        }

        $row++;

        // ── Legend ────────────────────────────────────────────────────────────
        $row = $this->writeLegend($sheet, $row);

        // Auto-size first column
        $sheet->getColumnDimension('A')->setWidth(38);

        return $spreadsheet;
    }

    // ── Block writers ─────────────────────────────────────────────────────────

    private function writeStats(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $row, int $year): int
    {
        $allMeetings = Meeting::where('planning_year', $year)->whereNotNull('committee_type')->get();
        $total       = $allMeetings->count();
        $stats       = $allMeetings->groupBy('jee_status')->map->count();

        $sheet->setCellValue('A' . $row, 'RÉCAPITULATIF ' . $year);
        $this->applyStyle($sheet, 'A' . $row, [
            'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FF374151']],
        ]);
        $row++;

        $labels  = Meeting::JEE_STATUSES;
        $col     = 1;
        foreach ($labels as $key => $label) {
            $count  = $stats->get($key, 0);
            $pct    = $total > 0 ? round($count / $total * 100) : 0;
            $colRef = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($colRef . $row, $label . "\n" . $count . ' réunions (' . $pct . '%)');
            $this->applyStyle($sheet, $colRef . $row, [
                'font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => self::ARGB[$key]['text']]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::ARGB[$key]['bg']]],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                    'wrapText'   => true,
                ],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']]],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(36);
            $col++;
        }

        return $row + 2;
    }

    private function writeSectionHeader(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $row, string $label): int
    {
        $sheet->setCellValue('A' . $row, $label);
        $sheet->mergeCells('A' . $row . ':N' . $row);
        $this->applyStyle($sheet, 'A' . $row . ':N' . $row, [
            'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 1],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(24);

        return $row + 1;
    }

    private function writeSectionRowHeader(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $row, string $name, string $sub): int
    {
        $sheet->setCellValue('A' . $row, $name . "\n" . $sub);
        $this->applyStyle($sheet, 'A' . $row, [
            'font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FF1F2937']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF3F4F6']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']]],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(28);

        return $row;
    }

    /**
     * @param \Illuminate\Support\Collection<string, Meeting> $meetings
     * @param array<string, string> $periods
     */
    private function writePeriodRow(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        int $row,
        string $committeeType,
        ?int $gttId,
        array $periods,
        \Illuminate\Support\Collection $meetings,
    ): int {
        // Period header sub-row
        $col = 2;
        foreach ($periods as $period => $label) {
            $colRef = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($colRef . $row, $label);
            $this->applyStyle($sheet, $colRef . $row, [
                'font' => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FF6B7280']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF9FAFB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE5E7EB']],
                ],
            ]);
            $sheet->getColumnDimensionByColumn($col)->setWidth(18);
            $col++;
        }
        $sheet->getRowDimension($row)->setRowHeight(18);
        $row++;

        // Status row
        $col = 2;
        foreach ($periods as $period => $label) {
            $key       = $committeeType . '|' . ($gttId ?? '') . '|' . $period;
            $meeting   = $meetings->get($key);
            $jeeStatus = $meeting?->jee_status ?? 'not_done';
            $statusLabel = Meeting::JEE_STATUSES[$jeeStatus] ?? '—';
            $icon        = match ($jeeStatus) {
                'not_done'    => '✗',
                'launched'    => '▶',
                'in_progress' => '⚙',
                'completed'   => '✓',
                default       => '?',
            };

            $colRef = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($colRef . $row, $icon . ' ' . $statusLabel);
            $this->applyStyle($sheet, $colRef . $row, [
                'font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => self::ARGB[$jeeStatus]['text']]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::ARGB[$jeeStatus]['bg']]],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']],
                ],
            ]);
            $col++;
        }
        $sheet->getRowDimension($row)->setRowHeight(28);

        return $row + 1;
    }

    private function writeLegend(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $row): int
    {
        $sheet->setCellValue('A' . $row, 'LÉGENDE — CODE COULEUR JEE');
        $this->applyStyle($sheet, 'A' . $row, [
            'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FF374151']],
        ]);
        $row++;

        $col = 1;
        foreach (Meeting::JEE_STATUSES as $key => $label) {
            $icon   = match ($key) {
                'not_done'    => '✗',
                'launched'    => '▶',
                'in_progress' => '⚙',
                'completed'   => '✓',
                default       => '?',
            };
            $colRef = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($colRef . $row, $icon . ' ' . $label);
            $this->applyStyle($sheet, $colRef . $row, [
                'font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => self::ARGB[$key]['text']]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::ARGB[$key]['bg']]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFFFFFFF']]],
            ]);
            $col++;
        }
        $sheet->getRowDimension($row)->setRowHeight(28);

        return $row + 1;
    }

    // ── Utility ───────────────────────────────────────────────────────────────

    /** @param array<string, mixed> $styleArray */
    private function applyStyle(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $range, array $styleArray): void
    {
        $sheet->getStyle($range)->applyFromArray($styleArray);
    }
}
