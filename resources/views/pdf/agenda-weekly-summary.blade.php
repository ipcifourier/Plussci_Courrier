<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Synthèse hebdomadaire Agenda</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; margin: 0; padding: 20px; }
        h1 { font-size: 15px; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #555; font-size: 9px; margin-bottom: 20px; }
        .section-title { font-weight: bold; font-size: 12px; margin: 18px 0 6px; border-bottom: 2px solid #1e3a5f; padding-bottom: 3px; color: #1e3a5f; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th { background: #1e3a5f; color: #fff; padding: 5px 7px; text-align: left; font-size: 9px; }
        td { padding: 5px 7px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        tr:nth-child(even) td { background: #f9fafb; }
        .empty { color: #aaa; font-style: italic; }
        .footer { margin-top: 30px; font-size: 8px; color: #aaa; text-align: center; }
    </style>
</head>
<body>

<h1>Synthèse hebdomadaire — Agenda</h1>
<p class="subtitle">
    Semaine du {{ $weekStart->translatedFormat('d F Y') }} au {{ $weekEnd->translatedFormat('d F Y Y') }}
    &nbsp;|&nbsp; Généré le {{ $generatedAt->format('d/m/Y à H:i') }}
    @if($generatedBy) &nbsp;|&nbsp; Par {{ $generatedBy->name }} @endif
</p>

{{-- Rendez-vous --}}
<div class="section-title">Rendez-vous ({{ $appointments->count() }})</div>
@if($appointments->isEmpty())
    <p class="empty">Aucun rendez-vous cette semaine.</p>
@else
<table>
    <thead>
        <tr>
            <th>Titre</th><th>Type</th><th>Début</th><th>Fin</th>
            <th>Lieu</th><th>Créé par</th><th>Assigné à</th><th>Statut</th>
        </tr>
    </thead>
    <tbody>
        @foreach($appointments as $a)
        <tr>
            <td>{{ $a->title ?? '—' }}</td>
            <td>{{ $a->type ?? '—' }}</td>
            <td>{{ optional($a->starts_at)?->format('d/m H:i') ?? '—' }}</td>
            <td>{{ optional($a->ends_at)?->format('d/m H:i') ?? '—' }}</td>
            <td>{{ $a->location ?? '—' }}</td>
            <td>{{ $a->creator?->name ?? '—' }}</td>
            <td>{{ $a->assignee?->name ?? '—' }}</td>
            <td>{{ $a->status ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- Réunions --}}
<div class="section-title">Réunions ({{ $meetings->count() }})</div>
@if($meetings->isEmpty())
    <p class="empty">Aucune réunion cette semaine.</p>
@else
<table>
    <thead>
        <tr>
            <th>Titre</th><th>Début</th><th>Fin</th>
            <th>Lieu</th><th>Facilitateur</th><th>Participants</th><th>Statut</th>
        </tr>
    </thead>
    <tbody>
        @foreach($meetings as $m)
        <tr>
            <td>{{ $m->title ?? '—' }}</td>
            <td>{{ optional($m->starts_at)?->format('d/m H:i') ?? '—' }}</td>
            <td>{{ optional($m->ends_at)?->format('d/m H:i') ?? '—' }}</td>
            <td>{{ $m->location ?? '—' }}</td>
            <td>{{ $m->facilitator?->name ?? '—' }}</td>
            <td>{{ $m->participants?->pluck('name')->implode(', ') ?? '—' }}</td>
            <td>{{ $m->status ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="footer">PLUSS.CI — Document généré le {{ $generatedAt->format('d/m/Y H:i') }}</div>

</body>
</html>
