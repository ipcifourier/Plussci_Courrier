<?php

namespace App\Services;

use App\Models\Report;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ReportExportService
{
    public function exportPdf(Report $report): Response
    {
        $content = $this->buildContent($report);

        $html = view('exports.report-template', [
            'report' => $report,
            'content' => nl2br(e($content)),
        ])->render();

        return Pdf::loadHTML($html)
            ->setPaper('a4')
            ->download($this->filename($report, 'pdf'));
    }

    public function exportWord(Report $report): Response
    {
        $content = $this->buildContent($report);

        $html = view('exports.report-template-word', [
            'report' => $report,
            'content' => nl2br(e($content)),
        ])->render();

        return response($html)
            ->header('Content-Type', 'application/msword; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $this->filename($report, 'doc') . '"');
    }

    public function buildContent(Report $report): string
    {
        $template = $report->template?->content_template;

        if (blank($template)) {
            return "Rapport {$report->reference}\n\nObjet: {$report->objet}";
        }

        $participants = collect($report->participants_json ?? [])->filter()->implode(', ');

        $replacements = [
            '{{reference}}' => $report->reference,
            '{{objet}}' => $report->objet,
            '{{categorie}}' => $report->category?->name ?? '-',
            '{{lieu}}' => $report->lieu ?? '-',
            '{{date_debut}}' => optional($report->date_start)?->format('d/m/Y') ?? '-',
            '{{date_fin}}' => optional($report->date_end)?->format('d/m/Y') ?? '-',
            '{{organisateur}}' => $report->organizer?->name ?? '-',
            '{{participants}}' => $participants ?: '-',
            '{{mission_courrier}}' => $report->missionCourrier?->reference ?? '-',
            '{{tdr_document}}' => $report->tdrDocument?->reference_doc ?? '-',
        ];

        return Str::of($template)->replace(array_keys($replacements), array_values($replacements))->value();
    }

    private function filename(Report $report, string $extension): string
    {
        return sprintf('rapport-%s.%s', Str::lower($report->reference), $extension);
    }
}
