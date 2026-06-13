<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves a private TDR or rapport document for download.
 *
 * GET /admin/reunions-planning/download/{meeting}/{type}
 */
class MeetingDocumentDownloadController extends Controller
{
    public function __invoke(Request $request, Meeting $meeting, string $type): StreamedResponse
    {
        if (! Auth::check()) {
            abort(401);
        }

        if (! in_array($type, ['tdr', 'rapport'], true)) {
            abort(404);
        }

        $path = $type === 'tdr' ? $meeting->tdr_path : $meeting->rapport_path;

        if (! $path || ! Storage::disk('private')->exists($path)) {
            abort(404);
        }

        $filename = ($type === 'tdr' ? 'TDR' : 'Rapport') . '_' . $meeting->planning_year . '_' . $meeting->planning_period . '.pdf';

        return response()->streamDownload(function () use ($path): void {
            echo Storage::disk('private')->get($path);
        }, $filename);
    }
}
