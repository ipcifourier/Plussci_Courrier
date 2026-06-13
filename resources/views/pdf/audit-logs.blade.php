<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Journal d'audit</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1a1a1a; margin: 0; padding: 16px; }
        h1 { font-size: 14px; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #555; font-size: 8px; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #1e3a5f; color: #fff; padding: 5px 6px; text-align: left; font-size: 8px; }
        td { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; vertical-align: top; word-break: break-all; }
        tr:nth-child(even) td { background: #f9fafb; }
        .footer { margin-top: 20px; font-size: 8px; color: #aaa; text-align: center; }
        .filters { margin-bottom: 12px; padding: 6px 10px; background: #f3f4f6; border-left: 3px solid #1e3a5f; font-size: 9px; }
    </style>
</head>
<body>

<h1>Journal d'audit — PLUSS.CI</h1>
<p class="subtitle">Généré le {{ $generatedAt->format('d/m/Y à H:i') }}</p>

@php
    $activeFilters = array_filter($filters);
@endphp
@if($activeFilters)
<div class="filters">
    <strong>Filtres appliqués :</strong>
    @foreach($activeFilters as $key => $value)
        <span>{{ $key }}: <strong>{{ $value }}</strong></span>{{ ! $loop->last ? ' &nbsp;|&nbsp; ' : '' }}
    @endforeach
</div>
@endif

<table>
    <thead>
        <tr>
            <th style="width:12%">Date</th>
            <th style="width:14%">Acteur</th>
            <th style="width:14%">Action</th>
            <th style="width:14%">Entité</th>
            <th style="width:6%">ID</th>
            <th style="width:12%">IP</th>
            <th style="width:14%">Avant</th>
            <th style="width:14%">Après</th>
        </tr>
    </thead>
    <tbody>
        @forelse($logs as $log)
        <tr>
            <td>{{ optional($log->created_at)?->format('d/m/Y H:i') }}</td>
            <td>{{ $log->actor?->name ?? 'Système' }}</td>
            <td>{{ $log->action }}</td>
            <td>{{ class_basename($log->entity_type ?? '') }}</td>
            <td>{{ $log->entity_id ?? '' }}</td>
            <td>{{ $log->ip_address ?? '' }}</td>
            <td>{{ $log->before_json ? Str::limit(json_encode($log->before_json, JSON_UNESCAPED_UNICODE), 80) : '' }}</td>
            <td>{{ $log->after_json ? Str::limit(json_encode($log->after_json, JSON_UNESCAPED_UNICODE), 80) : '' }}</td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;color:#aaa;font-style:italic;">Aucun enregistrement trouvé.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    {{ $logs->count() }} enregistrement(s) — PLUSS.CI — {{ $generatedAt->format('d/m/Y H:i') }}
</div>

</body>
</html>
