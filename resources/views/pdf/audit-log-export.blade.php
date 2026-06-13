<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8"/>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; margin: 0; }
    h1 { font-size: 14px; color: #d97706; margin: 0 0 4px; }
    .meta { font-size: 8px; color: #6b7280; margin-bottom: 8px; }
    .filters { background: #fef3c7; border: 1px solid #fcd34d; padding: 4px 8px; border-radius: 4px; margin-bottom: 10px; font-size: 8px; }
    table { width: 100%; border-collapse: collapse; }
    thead tr { background: #d97706; color: #fff; }
    th { padding: 4px 6px; text-align: left; font-size: 8px; font-weight: 600; }
    td { padding: 3px 6px; border-bottom: 1px solid #f3f4f6; font-size: 8px; vertical-align: top; }
    tr:nth-child(even) td { background: #fafafa; }
    .badge { display: inline-block; padding: 1px 4px; border-radius: 3px; background: #e5e7eb; font-size: 7px; }
    .footer { position: fixed; bottom: 10px; left: 0; right: 0; text-align: center; font-size: 7px; color: #9ca3af; }
    .page-break { page-break-after: always; }
</style>
</head>
<body>
<h1>Journal d'audit — PLUSS.CI</h1>
<div class="meta">Généré le {{ $generatedAt }} — {{ $logs->count() }} entrée(s)</div>

@if(!empty($filters))
<div class="filters">
    <strong>Filtres appliqués :</strong>
    @foreach($filters as $label => $val)
        {{ $label }}: <strong>{{ $val }}</strong>&nbsp;&nbsp;
    @endforeach
</div>
@endif

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Acteur</th>
            <th>Action</th>
            <th>Entité</th>
            <th>ID</th>
            <th>IP</th>
            <th>Modifications</th>
        </tr>
    </thead>
    <tbody>
    @foreach($logs as $log)
        <tr>
            <td>{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
            <td>{{ $log->actor?->name ?? 'Système' }}</td>
            <td><span class="badge">{{ $log->action }}</span></td>
            <td>{{ class_basename($log->entity_type ?? '') }}</td>
            <td>{{ $log->entity_id ?? '—' }}</td>
            <td>{{ $log->ip_address ?? '—' }}</td>
            <td>
                @php
                    $before = is_array($log->before_json) ? $log->before_json : [];
                    $after  = is_array($log->after_json) ? $log->after_json : [];
                    $allKeys = array_unique(array_merge(array_keys($before), array_keys($after)));
                    $changes = [];
                    foreach ($allKeys as $k) {
                        $b = array_key_exists($k, $before) ? (is_array($before[$k]) ? json_encode($before[$k]) : $before[$k]) : null;
                        $a = array_key_exists($k, $after)  ? (is_array($after[$k])  ? json_encode($after[$k])  : $after[$k])  : null;
                        if ($b !== $a) $changes[] = "{$k}: " . ($b ?? '∅') . " → " . ($a ?? '∅');
                    }
                @endphp
                {{ implode('; ', $changes) }}
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="footer">PLUSS.CI — Journal d'audit — Confidentiel — {{ $generatedAt }}</div>
</body>
</html>
