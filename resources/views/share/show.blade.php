<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document partagé — {{ $document->titre }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f4f6f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.10);
            max-width: 560px;
            width: 100%;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: #fff;
            padding: 2rem;
        }
        .card-header .badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 0.75rem;
        }
        .card-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.3;
        }
        .card-header .ref {
            margin-top: 0.35rem;
            font-size: 0.875rem;
            opacity: 0.85;
        }
        .card-body { padding: 2rem; }
        .meta-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.925rem;
        }
        .meta-row:last-child { border-bottom: none; }
        .meta-label {
            font-weight: 600;
            color: #64748b;
            min-width: 110px;
        }
        .meta-value { color: #1e293b; }
        .perms {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 1.25rem;
        }
        .perm-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            border-radius: 20px;
            padding: 0.3rem 0.8rem;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .perm-chip.download { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
        .perm-chip.comment  { background: #fdf4ff; color: #7e22ce; border-color: #e9d5ff; }
        .actions { margin-top: 1.75rem; display: flex; flex-direction: column; gap: 0.75rem; }
        .btn {
            display: block;
            width: 100%;
            padding: 0.85rem;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: opacity 0.15s;
        }
        .btn:hover { opacity: 0.88; }
        .btn-primary { background: #1d4ed8; color: #fff; }
        .btn-outline { background: #fff; color: #374151; border: 1.5px solid #d1d5db; }
        .footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 1rem 2rem;
            font-size: 0.8rem;
            color: #94a3b8;
            text-align: center;
        }
        .shared-by {
            background: #f1f5f9;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            color: #475569;
            margin-top: 1.5rem;
        }
        .shared-by strong { color: #1e293b; }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <div class="badge">{{ $document->type_document ?? 'Document' }}</div>
            <h1>{{ $document->titre }}</h1>
            @if($document->reference_doc)
                <p class="ref">Réf. {{ $document->reference_doc }}</p>
            @endif
        </div>

        <div class="card-body">
            <div class="meta-row">
                <span class="meta-label">État</span>
                <span class="meta-value">{{ $document->etat_cycle_vie }}</span>
            </div>
            @if($document->auteur_id)
            <div class="meta-row">
                <span class="meta-label">Auteur</span>
                <span class="meta-value">{{ $document->auteur?->name ?? '—' }}</span>
            </div>
            @endif
            <div class="meta-row">
                <span class="meta-label">Créé le</span>
                <span class="meta-value">{{ $document->created_at->format('d/m/Y') }}</span>
            </div>
            @if($share->expires_at)
            <div class="meta-row">
                <span class="meta-label">Lien expire</span>
                <span class="meta-value">{{ $share->expires_at->format('d/m/Y à H:i') }}</span>
            </div>
            @endif

            <div class="perms">
                @if($share->can_view)
                <span class="perm-chip">👁 Consultation</span>
                @endif
                @if($share->can_download)
                <span class="perm-chip download">⬇ Téléchargement</span>
                @endif
                @if($share->can_comment)
                <span class="perm-chip comment">💬 Commentaires</span>
                @endif
            </div>

            @if($share->can_download && $mediaItems->isNotEmpty())
            <div class="actions">
                @foreach($mediaItems as $media)
                <a href="{{ route('share.download', [$share->token, $media->id]) }}"
                   class="btn btn-primary">
                    ⬇ Télécharger {{ $mediaItems->count() > 1 ? $media->file_name : 'le fichier' }}
                </a>
                @endforeach
            </div>
            @elseif(!$share->can_download)
            <div class="actions">
                <div class="btn btn-outline" style="cursor:default; opacity:.6;">
                    ⛔ Téléchargement non autorisé pour ce partage
                </div>
            </div>
            @endif

            <div class="shared-by">
                Partagé par <strong>{{ $share->sharedBy->name }}</strong>
                le {{ $share->created_at->format('d/m/Y') }}.
            </div>
        </div>

        <div class="footer">
            Courrier+ · Accès sécurisé par lien — {{ config('app.name') }}
        </div>
    </div>
</body>
</html>
