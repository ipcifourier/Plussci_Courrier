<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\SyncDevice;
use App\Models\User;
use App\Services\DocumentAccessService;
use App\Services\DocumentImportService;
use App\Services\SyncSettingsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SyncClientController extends Controller
{
    public function __construct(
        private readonly DocumentAccessService $access,
        private readonly SyncSettingsService $syncSettings,
    ) {}

    public function ping(Request $request): JsonResponse
    {
        $device = $this->device($request);

        return response()->json([
            'ok' => true,
            'server_time' => now()->toIso8601String(),
            'device' => [
                'id' => $device->id,
                'name' => $device->name,
                'platform' => $device->platform,
            ],
        ]);
    }

    public function config(Request $request): JsonResponse
    {
        $user = $this->user($request);

        return response()->json([
            'ok' => true,
            'global' => $this->syncSettings->globalConfig(),
            'user' => $this->syncSettings->userConfig($user),
        ]);
    }

    public function changes(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $device = $this->device($request);
        $global = $this->syncSettings->globalConfig();
        $userConfig = $this->syncSettings->userConfig($user);

        if (! $global['enabled'] || ! $userConfig['enabled']) {
            return response()->json([
                'ok' => true,
                'disabled' => true,
                'files' => [],
                'server_time' => now()->toIso8601String(),
            ]);
        }

        $since = $this->parseSince((string) $request->query('since', ''));
        $limit = min(
            (int) $global['max_files_per_pull'],
            max(1, (int) $request->integer('limit', (int) $global['max_files_per_pull']))
        );

        $candidateDocuments = Document::query()
            ->select(['id', 'titre'])
            ->orderByDesc('updated_at')
            ->limit(800)
            ->get();

        $allowedDocumentIds = $candidateDocuments
            ->filter(fn (Document $document): bool => $this->access->canDownload($user, $document))
            ->pluck('id')
            ->all();

        if ($allowedDocumentIds === []) {
            return response()->json([
                'ok' => true,
                'disabled' => false,
                'files' => [],
                'server_time' => now()->toIso8601String(),
            ]);
        }

        $mediaRows = Media::query()
            ->where('model_type', Document::class)
            ->where('collection_name', 'documents')
            ->whereIn('model_id', $allowedDocumentIds)
            ->where('updated_at', '>=', $since)
            ->orderBy('updated_at')
            ->limit($limit)
            ->get(['id', 'model_id', 'file_name', 'mime_type', 'size', 'updated_at']);

        $titles = Document::query()
            ->whereIn('id', $mediaRows->pluck('model_id')->unique()->all())
            ->pluck('titre', 'id');

        $files = $mediaRows->map(function (Media $media) use ($titles): array {
            return [
                'media_id' => $media->id,
                'document_id' => (int) $media->model_id,
                'document_title' => (string) ($titles[(int) $media->model_id] ?? ('Document #' . (int) $media->model_id)),
                'file_name' => (string) $media->file_name,
                'mime_type' => (string) ($media->mime_type ?? 'application/octet-stream'),
                'size' => (int) $media->size,
                'updated_at' => $media->updated_at?->toIso8601String(),
                'download_url' => route('sync-client.download', ['mediaId' => $media->id]),
            ];
        })->values();

        $device->forceFill([
            'last_synced_at' => now(),
        ])->save();

        return response()->json([
            'ok' => true,
            'disabled' => false,
            'files' => $files,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function download(Request $request, int $mediaId)
    {
        $user = $this->user($request);

        /** @var Media|null $media */
        $media = Media::query()
            ->where('id', $mediaId)
            ->where('model_type', Document::class)
            ->where('collection_name', 'documents')
            ->first();

        abort_if(! $media, 404);

        /** @var Document|null $document */
        $document = Document::query()->find($media->model_id);

        abort_if(! $document, 404);
        abort_if(! $this->access->canDownload($user, $document), 403);

        return response()->download(
            $media->getPath(),
            $media->file_name,
            ['Content-Type' => (string) ($media->mime_type ?? 'application/octet-stream')]
        );
    }

    public function scanUpload(Request $request, DocumentImportService $importer): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:102400'],
        ]);

        $user   = $this->user($request);
        $device = $this->device($request);

        /** @var \Illuminate\Http\UploadedFile $uploadedFile */
        $uploadedFile = $request->file('file');

        $document = $importer->import($uploadedFile, [
            'titre'         => pathinfo((string) $uploadedFile->getClientOriginalName(), PATHINFO_FILENAME),
            'type_document' => $request->input('type_document', 'Document'),
            'dossier_id'    => $request->integer('dossier_id') ?: null,
            'auteur_id'     => $user->id,
            'source'        => 'scan_desktop',
            'source_meta'   => json_encode([
                'device_id'     => $device->id,
                'device_name'   => $device->name,
                'original_name' => $uploadedFile->getClientOriginalName(),
            ], JSON_UNESCAPED_UNICODE),
        ]);

        return response()->json([
            'ok'          => true,
            'document_id' => $document->id,
            'reference'   => $document->reference_doc,
            'titre'       => $document->titre,
        ]);
    }

    private function parseSince(string $raw): Carbon
    {
        if (trim($raw) === '') {
            return now()->subDays(7);
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            return now()->subDays(7);
        }
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->attributes->get('syncUser');

        return $user;
    }

    private function device(Request $request): SyncDevice
    {
        /** @var SyncDevice $device */
        $device = $request->attributes->get('syncDevice');

        return $device;
    }
}
