<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport {{ $report->reference }}</title>
    <style>
        body { font-family: Cambria, serif; font-size: 12pt; color: #111; }
        h1 { font-size: 18pt; margin-bottom: 4pt; }
        p { margin: 6pt 0; }
        .meta { color: #444; font-size: 10pt; }
    </style>
</head>
<body>
    <h1>{{ $report->objet }}</h1>
    <p class="meta">Reference: {{ $report->reference }} | Categorie: {{ $report->category?->name ?? '-' }}</p>
    <p><strong>Lieu:</strong> {{ $report->lieu ?? '-' }}</p>
    <p><strong>Periode:</strong> {{ optional($report->date_start)->format('d/m/Y') ?? '-' }} au {{ optional($report->date_end)->format('d/m/Y') ?? '-' }}</p>
    <p><strong>Organisateur:</strong> {{ $report->organizer?->name ?? '-' }}</p>
    <p><strong>Mission:</strong> {{ $report->missionCourrier?->reference ?? '-' }}</p>
    <p><strong>TDR:</strong> {{ $report->tdrDocument?->reference_doc ?? '-' }}</p>
    <hr>
    <p>{!! $content !!}</p>
</body>
</html>
