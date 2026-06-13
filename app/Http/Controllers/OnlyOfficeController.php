<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class OnlyOfficeController extends Controller
{
    /**
     * Map a file extension to an OnlyOffice document type.
     */
    public static function resolveDocumentType(string $extension): string
    {
        $extension = strtolower($extension);

        if (in_array($extension, ['doc', 'docx', 'odt', 'rtf', 'txt'], true)) {
            return 'word';
        }

        if (in_array($extension, ['xls', 'xlsx', 'ods', 'csv'], true)) {
            return 'cell';
        }

        if (in_array($extension, ['ppt', 'pptx', 'odp'], true)) {
            return 'slide';
        }

        return 'word';
    }

    /**
     * Render the OnlyOffice editor page for a document.
     */
    public function editor(Request $request, Document $document): View
    {
        $user = Auth::user();
        abort_if(! $user, 403);

        /** @var \App\Models\User $user */
        abort_unless(
            $user->hasRole('Super Admin') || $user->hasPermissionTo('ged.documents.update'),
            403
        );

        $media = $document->getFirstMedia('documents');
        abort_unless($media, 404, 'Aucun fichier attaché à ce document.');

        $callbackUrl = route('onlyoffice.callback', [
            'document' => $document->id,
            'media'    => $media->id,
        ]);

        $fileUrl    = $media->getTemporaryUrl(now()->addHours(2));
        $documentKey = md5($document->id . '-' . ($media->updated_at?->timestamp ?? 0));

        app(AuditLogger::class)->log(
            action: 'document.office_editor.open',
            entity: $document,
            meta: ['document_id' => $document->id, 'media_id' => $media->id],
        );

        return view('onlyoffice.editor', compact('document', 'media', 'callbackUrl', 'fileUrl', 'documentKey', 'user'));
    }

    /**
     * Handle the OnlyOffice save callback.
     * Called by the OnlyOffice Document Server when a document is saved.
     */
    public function callback(Request $request, Document $document, Media $media): JsonResponse
    {
        $payload = $request->json()->all();
        $status  = (int) ($payload['status'] ?? 0);

        /*
         * OnlyOffice callback statuses:
         *   0 = being edited
         *   1 = being edited (no changes, force save)
         *   2 = ready to save (all editors closed)
         *   3 = save error
         *   6 = force-save
         *   7 = force-save error
         */
        if (! in_array($status, [2, 6], true)) {
            return response()->json(['error' => 0]);
        }

        $downloadUrl = $payload['url'] ?? null;

        if (! $downloadUrl) {
            return response()->json(['error' => 1]);
        }

        try {
            $fileContents = file_get_contents($downloadUrl);

            if ($fileContents === false) {
                throw new \RuntimeException('Cannot download file from OnlyOffice server.');
            }

            $tmpPath = tempnam(sys_get_temp_dir(), 'onlyoffice_') . '.' . $media->extension;
            file_put_contents($tmpPath, $fileContents);

            $document->addMedia($tmpPath)
                ->usingName($media->name)
                ->usingFileName($media->file_name)
                ->toMediaCollection('documents');

            app(AuditLogger::class)->log(
                action: 'document.office_editor.saved',
                entity: $document,
                meta: ['document_id' => $document->id, 'status' => $status],
            );
        } catch (\Throwable $e) {
            Log::error('OnlyOffice callback error', [
                'document_id' => $document->id,
                'error'       => $e->getMessage(),
            ]);

            return response()->json(['error' => 1]);
        }

        return response()->json(['error' => 0]);
    }
}
