<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $document->reference_doc }} - Coedition</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            background: #eef6ff;
            color: #0f2f5f;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.75rem 1rem;
            background: #dbeafe;
            border-bottom: 1px solid #9fc4eb;
        }

        .topbar .meta {
            display: grid;
            gap: 0.25rem;
        }

        .topbar .title {
            font-weight: 800;
        }

        .topbar .mode {
            font-size: 0.9rem;
            color: #274d79;
        }

        .topbar a {
            color: #0f2f5f;
            text-decoration: none;
            font-weight: 700;
            border: 1px solid #9fc4eb;
            background: #ffffff;
            border-radius: 8px;
            padding: 0.4rem 0.7rem;
        }

        #onlyoffice-editor {
            width: 100%;
            height: calc(100% - 74px);
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="meta">
            <div class="title">{{ $document->reference_doc }} - {{ $media->file_name }}</div>
            <div class="mode">Mode: {{ $canEdit ? 'Coedition' : 'Lecture seule' }}</div>
        </div>
        <a href="{{ \App\Filament\Resources\Documents\DocumentResource::getUrl('view', ['record' => $document]) }}">Retour au document</a>
    </div>

    <div id="onlyoffice-editor"></div>
    <script id="onlyoffice-config" type="application/json">{{ json_encode($editorConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</script>

    <script src="{{ $documentServerUrl }}/web-apps/apps/api/documents/api.js"></script>
    <script>
        const rawConfig = document.getElementById('onlyoffice-config')?.textContent ?? '{}';
        const config = JSON.parse(rawConfig);
        // eslint-disable-next-line no-unused-vars
        const docEditor = new DocsAPI.DocEditor('onlyoffice-editor', config);
    </script>
</body>
</html>
