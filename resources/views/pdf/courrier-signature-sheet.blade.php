<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Feuille de Signature — {{ $courrier->reference }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; margin: 0; padding: 20px; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .subtitle { text-align: center; color: #555; margin-bottom: 20px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { background: #1e3a5f; color: #fff; padding: 6px 8px; text-align: left; font-size: 10px; }
        td { padding: 6px 8px; border-bottom: 1px solid #ddd; vertical-align: top; }
        tr:nth-child(even) td { background: #f5f7fa; }
        .section-title { font-weight: bold; font-size: 12px; margin: 16px 0 6px; border-bottom: 2px solid #1e3a5f; padding-bottom: 3px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 9px; font-weight: bold; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        .badge-gray { background: #f3f4f6; color: #374151; }
        .signature-box { border: 1px solid #ccc; height: 60px; margin-top: 6px; }
        .footer { margin-top: 30px; font-size: 9px; color: #888; text-align: center; }
    </style>
</head>
<body>

<h1>Feuille de Signature</h1>
<p class="subtitle">Généré le {{ $generatedAt->format('d/m/Y à H:i') }} — PLUSS.CI</p>

<div class="section-title">Informations du courrier</div>
<table>
    <tr>
        <td><strong>Référence</strong></td>
        <td>{{ $courrier->reference ?? '—' }}</td>
        <td><strong>Type</strong></td>
        <td>{{ $courrier->type ?? '—' }}</td>
    </tr>
    <tr>
        <td><strong>Objet</strong></td>
        <td colspan="3">{{ $courrier->objet ?? '—' }}</td>
    </tr>
    <tr>
        <td><strong>Correspondant</strong></td>
        <td>{{ $courrier->correspondant?->nom_structure ?? '—' }}</td>
        <td><strong>Date</strong></td>
        <td>{{ optional($courrier->date_reception_envoi)?->format('d/m/Y') ?? '—' }}</td>
    </tr>
    <tr>
        <td><strong>Priorité</strong></td>
        <td>{{ $courrier->priorite ?? '—' }}</td>
        <td><strong>Statut</strong></td>
        <td>{{ $courrier->statut ?? '—' }}</td>
    </tr>
    <tr>
        <td><strong>Confidentialité</strong></td>
        <td colspan="3">{{ $courrier->niveau_confidentialite ?? '—' }}</td>
    </tr>
</table>

@if($courrier->resume)
<div class="section-title">Résumé</div>
<table>
    <tr><td>{{ $courrier->resume }}</td></tr>
</table>
@endif

@if($courrier->imputations && $courrier->imputations->count())
<div class="section-title">Imputations</div>
<table>
    <thead>
        <tr>
            <th>Destinataire</th>
            <th>Instructions</th>
            <th>Statut traitement</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach($courrier->imputations as $imputation)
        <tr>
            <td>{{ $imputation->destinataire?->name ?? '—' }}</td>
            <td>{{ $imputation->instructions ?? '—' }}</td>
            <td>{{ $imputation->statut_traitement ?? '—' }}</td>
            <td>{{ optional($imputation->date_imputation)?->format('d/m/Y') ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

@if($courrier->approvals && $courrier->approvals->count())
<div class="section-title">Circuit d'approbation</div>
<table>
    <thead>
        <tr><th>Niveau</th><th>Approbateur</th><th>Décision</th><th>Commentaire</th><th>Date décision</th></tr>
    </thead>
    <tbody>
        @foreach($courrier->approvals->sortBy('level') as $approval)
        <tr>
            <td>N{{ $approval->level }}</td>
            <td>{{ $approval->approver?->name ?? '—' }}</td>
            <td>
                @if($approval->status === 'approved')
                    <span class="badge badge-green">Approuvé</span>
                @elseif($approval->status === 'rejected')
                    <span class="badge badge-red">Rejeté</span>
                @else
                    <span class="badge badge-yellow">En attente</span>
                @endif
            </td>
            <td>{{ $approval->comment ?? '—' }}</td>
            <td>{{ optional($approval->decided_at)?->format('d/m/Y H:i') ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="section-title">Signature finale</div>
<table>
    <tr>
        <td><strong>Signé par</strong></td>
        <td>{{ $courrier->signer?->name ?? '—' }}</td>
        <td><strong>Date de signature</strong></td>
        <td>{{ optional($courrier->signed_at)?->format('d/m/Y H:i') ?? '—' }}</td>
    </tr>
    @if($courrier->signature_comment)
    <tr>
        <td><strong>Commentaire</strong></td>
        <td colspan="3">{{ $courrier->signature_comment }}</td>
    </tr>
    @endif
</table>

@if(! $courrier->signed_at)
<div class="section-title">Espace de signature</div>
<table>
    <tr>
        <td width="50%">
            <strong>Nom &amp; Prénom :</strong> ___________________________<br><br>
            <strong>Titre :</strong> ___________________________<br><br>
            <strong>Date :</strong> ___________________________<br><br>
            <strong>Signature :</strong>
            <div class="signature-box"></div>
        </td>
        <td width="50%">
            <strong>Cachet de l'institution :</strong>
            <div class="signature-box" style="height: 80px;"></div>
        </td>
    </tr>
</table>
@endif

<div class="footer">
    Document généré par PLUSS.CI — {{ $generatedAt->format('d/m/Y H:i') }}
</div>

</body>
</html>
