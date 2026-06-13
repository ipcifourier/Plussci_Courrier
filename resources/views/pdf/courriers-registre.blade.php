<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Registre des courriers</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h1 { margin: 0 0 6px 0; font-size: 18px; }
        .meta { margin-bottom: 12px; }
        .meta div { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #777; padding: 6px; vertical-align: top; }
        th { background: #eee; text-align: left; }
        .muted { color: #666; }
    </style>
</head>
<body>
    <h1>Registre des courriers</h1>
    <div class="meta">
        <div><strong>Généré le :</strong> {{ $generatedAt->format('d/m/Y H:i') }}</div>
        <div><strong>Filtres :</strong>
            Type={{ $filters['type'] ?: 'Tous' }},
            Statut={{ $filters['statut'] ?: 'Tous' }},
            Période={{ $filters['date_debut'] ?: '...' }} à {{ $filters['date_fin'] ?: '...' }},
            Signés uniquement={{ !empty($filters['signed_only']) ? 'Oui' : 'Non' }}
        </div>
        <div><strong>Total :</strong> {{ $courriers->count() }} courrier(s)</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Référence</th>
                <th>Type</th>
                <th>Date</th>
                <th>Objet</th>
                <th>Correspondant</th>
                <th>Agent</th>
                <th>Statut</th>
                <th>Validation</th>
                <th>Signature</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($courriers as $courrier)
                <tr>
                    <td>{{ $courrier->reference }}</td>
                    <td>{{ $courrier->type }}</td>
                    <td>{{ optional($courrier->date_reception_envoi)->format('d/m/Y') }}</td>
                    <td>{{ $courrier->objet }}</td>
                    <td>{{ $courrier->correspondant?->nom_structure }}</td>
                    <td>{{ $courrier->initiateur?->name }}</td>
                    <td>{{ $courrier->statut }}</td>
                    <td>{{ $courrier->approval_status }}</td>
                    <td>
                        @if ($courrier->signed_at)
                            Signé le {{ $courrier->signed_at->format('d/m/Y H:i') }}
                            @if ($courrier->signer)
                                <br><span class="muted">par {{ $courrier->signer->name }}</span>
                            @endif
                        @else
                            Non signé
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">Aucun courrier trouvé avec ces filtres.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
