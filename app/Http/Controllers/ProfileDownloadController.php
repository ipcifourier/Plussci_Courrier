<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileDownloadController extends Controller
{
    /**
     * Serve a protected profile asset (avatar, certificate, etc.).
     * The {asset} parameter is a path relative to the `profile` disk.
     */
    public function __invoke(Request $request, string $asset): StreamedResponse
    {
        $user = Auth::user();
        abort_if(! $user, 403);

        // Prevent path traversal
        $asset = ltrim($asset, '/\\');
        abort_if(str_contains($asset, '..'), 400);

        // Assets are stored under users/{user_id}/...
        $path = 'users/' . $user->id . '/' . $asset;

        abort_unless(Storage::disk('local')->exists($path), 404);

        $fullPath = Storage::disk('local')->path($path);
        $mime     = mime_content_type($fullPath) ?: 'application/octet-stream';

        return response()->streamDownload(function () use ($path): void {
            $stream = Storage::disk('local')->readStream($path);
            if ($stream !== false) {
                fpassthru($stream);
                fclose($stream);
            }
        }, basename($asset), [
            'Content-Type'   => $mime,
            'Content-Length' => Storage::disk('local')->size($path),
        ]);
    }
}
