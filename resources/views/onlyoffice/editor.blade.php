<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Éditeur — {{ $document->titre }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f1f5f9; }
        .toolbar {
            display: flex; align-items: center; gap: 12px;
            background: #1e3a5f; color: #fff;
            padding: 10px 16px; font-size: 14px;
        }
        .toolbar a { color: #93c5fd; text-decoration: none; font-size: 13px; }
        .toolbar a:hover { text-decoration: underline; }
        .toolbar .title { font-weight: 600; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        #editor-container { width: 100%; height: calc(100vh - 48px); }
    </style>
</head>
<body>

<div class="toolbar">
    <a href="{{ url()->previous() }}">&larr; Retour</a>
    <span class="title">{{ $document->titre }}</span>
    <span style="font-size:12px;opacity:.7;">{{ $media->file_name }}</span>
</div>

<div id="editor-container"></div>

<script src="{{ config('services.onlyoffice.api_js_url', 'http://localhost:8888/web-apps/apps/api/documents/api.js') }}"></script>
<script>
    new DocsAPI.DocEditor('editor-container', {
        document: {
            fileType: "{{ $media->extension }}",
            key:      "{{ $documentKey }}",
            title:    "{{ addslashes($document->titre) }}",
            url:      "{{ $fileUrl }}",
            permissions: {
                comment:   true,
                download:  true,
                edit:      true,
                fillForms: true,
                print:     true,
            }
        },
        documentType: "{{ \App\Http\Controllers\OnlyOfficeController::resolveDocumentType($media->extension) }}",
        editorConfig: {
            callbackUrl: "{{ $callbackUrl }}",
            lang:        "fr",
            mode:        "edit",
            user: {
                id:   "{{ $user->id }}",
                name: "{{ addslashes($user->name) }}"
            }
        },
        height: "100%",
        width:  "100%",
    });
</script>
</body>
</html>
