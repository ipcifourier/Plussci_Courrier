<?php

namespace App\Http\Controllers;

use App\Models\Gtt;
use App\Models\Meeting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Handles TDR / Rapport document uploads for a planning meeting cell.
 *
 * POST /admin/reunions-planning/upload
 * Returns JSON { success, jee_status, doc_type, has_tdr, has_rapport }
 *
 * When doc_type = tdr   → auto-sets jee_status to 'launched'  (if currently 'not_done')
 * When doc_type = rapport → auto-sets jee_status to 'completed' (always)
 */
class MeetingDocumentUploadController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'document'       => 'required|file|mimes:pdf,doc,docx|max:10240',
            'doc_type'       => 'required|in:tdr,rapport',
            'committee_type' => 'required|string|max:100',
            'period'         => 'required|string|max:20',
            'year'           => 'required|integer|min:2000|max:2100',
            'gtt_id'         => 'nullable|integer|exists:gtts,id',
        ]);

        $gttId = isset($validated['gtt_id']) ? (int) $validated['gtt_id'] : null;

        $meeting = Meeting::firstOrCreate(
            [
                'committee_type'  => $validated['committee_type'],
                'planning_year'   => (int) $validated['year'],
                'planning_period' => $validated['period'],
                'gtt_id'          => $gttId,
            ],
            [
                'title'      => $this->buildTitle($validated['committee_type'], $validated['period'], (int) $validated['year'], $gttId),
                'starts_at'  => now(),
                'status'     => 'planned',
                'jee_status' => 'not_done',
            ]
        );

        $field     = $validated['doc_type'] === 'tdr' ? 'tdr_path' : 'rapport_path';
        $autoStatus = $validated['doc_type'] === 'tdr' ? 'launched' : 'completed';

        // Remove previous file
        if ($meeting->$field) {
            Storage::disk('private')->delete($meeting->$field);
        }

        $path = $request->file('document')->store(
            'meetings/' . $validated['year'],
            'private'
        );

        $updates = [$field => $path];

        // Auto-advance JEE status
        if ($validated['doc_type'] === 'tdr' && $meeting->jee_status === 'not_done') {
            $updates['jee_status'] = $autoStatus;
        } elseif ($validated['doc_type'] === 'rapport') {
            $updates['jee_status'] = $autoStatus;
        }

        $meeting->update($updates);
        $meeting->refresh();

        return response()->json([
            'success'     => true,
            'meeting_id'  => $meeting->id,
            'jee_status'  => $meeting->jee_status,
            'doc_type'    => $validated['doc_type'],
            'has_tdr'     => (bool) $meeting->tdr_path,
            'has_rapport' => (bool) $meeting->rapport_path,
        ]);
    }

    private function buildTitle(string $type, string $period, int $year, ?int $gttId): string
    {
        return match ($type) {
            'comite_veille'         => 'Comité de Veille – ' . $period . ' ' . $year,
            'comite_technique'      => 'Comité Technique – ' . $period . ' ' . $year,
            'secretariat_technique' => 'STM – ' . $period . ' ' . $year,
            'gtt'                   => ($gttId ? (Gtt::find($gttId)?->name ?? 'GTT') : 'GTT') . ' – ' . $period . ' ' . $year,
            default                 => 'Réunion – ' . $period . ' ' . $year,
        };
    }
}
