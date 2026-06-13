<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h1, h2 { margin: 0 0 8px 0; }
        .meta { margin-bottom: 14px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
        th { background: #f4f4f4; text-align: left; }
        .section { margin-top: 10px; }
        .empty { color: #777; font-style: italic; }
    </style>
</head>
<body>
    <h1>Synthese hebdomadaire Agenda</h1>
    <div class="meta">
        Periode: {{ $weekStart->format('d/m/Y') }} - {{ $weekEnd->format('d/m/Y') }}<br>
        Genere le: {{ $generatedAt->format('d/m/Y H:i') }}
    </div>

    <div class="section">
        <h2>1. Reunions de la semaine ({{ $meetings->count() }})</h2>
        @if($meetings->isEmpty())
            <p class="empty">Aucune reunion planifiee sur la periode.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Reunion</th>
                        <th>Debut</th>
                        <th>Fin</th>
                        <th>Lieu</th>
                        <th>Animateur</th>
                        <th>Statut</th>
                        <th>Diligences</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($meetings as $meeting)
                        <tr>
                            <td>{{ $meeting->title }}</td>
                            <td>{{ optional($meeting->starts_at)->format('d/m/Y H:i') }}</td>
                            <td>{{ optional($meeting->ends_at)->format('d/m/Y H:i') }}</td>
                            <td>{{ $meeting->location ?? '-' }}</td>
                            <td>{{ $meeting->facilitator?->name ?? '-' }}</td>
                            <td>{{ $meeting->status }}</td>
                            <td>{{ $meeting->tasks_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="section">
        <h2>2. Diligences en retard ({{ $overdueTasks->count() }})</h2>
        @if($overdueTasks->isEmpty())
            <p class="empty">Aucune diligence en retard.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Diligence</th>
                        <th>Reunion</th>
                        <th>Assigne a</th>
                        <th>Echeance</th>
                        <th>Priorite</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($overdueTasks as $task)
                        <tr>
                            <td>{{ $task->title }}</td>
                            <td>{{ $task->meeting?->title ?? '-' }}</td>
                            <td>{{ $task->assignee?->name ?? '-' }}</td>
                            <td>{{ optional($task->due_at)->format('d/m/Y H:i') }}</td>
                            <td>{{ $task->priority }}</td>
                            <td>{{ $task->status }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="section">
        <h2>3. Diligences a venir (7 jours) ({{ $upcomingTasks->count() }})</h2>
        @if($upcomingTasks->isEmpty())
            <p class="empty">Aucune diligence a venir sur 7 jours.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Diligence</th>
                        <th>Reunion</th>
                        <th>Assigne a</th>
                        <th>Echeance</th>
                        <th>Priorite</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($upcomingTasks as $task)
                        <tr>
                            <td>{{ $task->title }}</td>
                            <td>{{ $task->meeting?->title ?? '-' }}</td>
                            <td>{{ $task->assignee?->name ?? '-' }}</td>
                            <td>{{ optional($task->due_at)->format('d/m/Y H:i') }}</td>
                            <td>{{ $task->priority }}</td>
                            <td>{{ $task->status }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</body>
</html>
