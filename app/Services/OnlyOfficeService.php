<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class OnlyOfficeService
{
    private const SUPPORTED_EXTENSIONS = [
        'doc', 'docx', 'odt', 'rtf', 'txt',
        'xls', 'xlsx', 'ods', 'csv',
        'ppt', 'pptx', 'odp',
    ];

    public function isEnabled(): bool
    {
        return filled(config('onlyoffice.document_server_url'));
    }

    public function getPrimaryOfficeMedia(Document $document): ?Media
    {
        $mediaItems = $document->getMedia('documents');

        foreach ($mediaItems as $media) {
            if ($this->isSupportedMedia($media)) {
                return $media;
            }
        }

        return null;
    }

    public function isSupportedMedia(Media $media): bool
    {
        $ext = strtolower(pathinfo($media->file_name, PATHINFO_EXTENSION));

        return in_array($ext, self::SUPPORTED_EXTENSIONS, true);
    }

    public function buildEditorConfig(Document $document, User $user, Media $media, bool $canEdit): array
    {
        $ext = strtolower(pathinfo($media->file_name, PATHINFO_EXTENSION));

        $callbackUrl = URL::temporarySignedRoute(
            'onlyoffice.callback',
            now()->addDays(7),
            ['document' => $document->id, 'media' => $media->id]
        );

        $config = [
            'document' => [
                'title' => $media->file_name,
                'url' => url($media->getUrl()),
                'fileType' => $ext,
                'key' => $this->buildDocumentKey($document, $media),
                'permissions' => [
                    'edit' => $canEdit,
                    'review' => $canEdit,
                    'download' => true,
                    'print' => true,
                    'copy' => true,
                    'chat' => true,
                ],
            ],
            'documentType' => $this->mapDocumentType($ext),
            'editorConfig' => [
                'mode' => $canEdit ? 'edit' : 'view',
                'lang' => 'fr',
                'callbackUrl' => $callbackUrl,
                'user' => [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                ],
                'customization' => [
                    'autosave' => true,
                    'forcesave' => true,
                    'compactHeader' => false,
                    'toolbarNoTabs' => false,
                ],
            ],
        ];

        $secret = (string) config('onlyoffice.jwt_secret', '');

        if ($secret !== '') {
            $config['token'] = $this->encodeJwt($config, $secret);
        }

        return $config;
    }

    public function verifyCallbackToken(?string $token): bool
    {
        $secret = (string) config('onlyoffice.jwt_secret', '');

        if ($secret === '') {
            return true;
        }

        if (! $token) {
            return false;
        }

        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return false;
        }

        [$header64, $payload64, $signature64] = $parts;

        $expected = $this->base64UrlEncode(hash_hmac('sha256', $header64 . '.' . $payload64, $secret, true));

        return hash_equals($expected, $signature64);
    }

    public function extractCallbackToken(array $payload, ?string $authorizationHeader): ?string
    {
        if (isset($payload['token']) && is_string($payload['token'])) {
            return $payload['token'];
        }

        if (! $authorizationHeader) {
            return null;
        }

        if (str_starts_with($authorizationHeader, 'Bearer ')) {
            return substr($authorizationHeader, 7);
        }

        return null;
    }

    private function buildDocumentKey(Document $document, Media $media): string
    {
        $raw = implode('|', [
            $document->id,
            $media->id,
            $media->updated_at?->timestamp ?? time(),
        ]);

        return substr(hash('sha256', $raw), 0, 48);
    }

    private function mapDocumentType(string $ext): string
    {
        return match ($ext) {
            'xls', 'xlsx', 'ods', 'csv' => 'spreadsheet',
            'ppt', 'pptx', 'odp' => 'presentation',
            default => 'word',
        };
    }

    private function encodeJwt(array $payload, string $secret): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];

        $header64 = $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $payload64 = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signature = hash_hmac('sha256', $header64 . '.' . $payload64, $secret, true);

        return $header64 . '.' . $payload64 . '.' . $this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
