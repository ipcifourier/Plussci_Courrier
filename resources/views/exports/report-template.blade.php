<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport {{ $report->reference }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        .header { margin-bottom: 18px; border-bottom: 1px solid #d1d5db; padding-bottom: 10px; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 4px; }
        .meta { font-size: 11px; color: #4b5563; }
        .block { margin-top: 12px; }
        .label { font-weight: 600; color: #374151; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">{{ $report->objet }}</div>
        <div class="meta">Reference: {{ $report->reference }} | Categorie: {{ $report->category?->name ?? '-' }}</div>
    </div>

    <div class="block"><span class="label">Lieu:</span> {{ $report->lieu ?? '-' }}</div>
    <div class="block"><span class="label">Periode:</span> {{ optional($report->date_start)->format('d/m/Y') ?? '-' }} au {{ optional($report->date_end)->format('d/m/Y') ?? '-' }}</div>
    <div class="block"><span class="label">Organisateur:</span> {{ $report->organizer?->name ?? '-' }}</div>
    <div class="block"><span class="label">Mission:</span> {{ $report->missionCourrier?->reference ?? '-' }}</div>
    <div class="block"><span class="label">TDR:</span> {{ $report->tdrDocument?->reference_doc ?? '-' }}</div>

    <div class="block" style="margin-top: 22px;">
        {!! $content !!}
    </div>
</body>
</html>
