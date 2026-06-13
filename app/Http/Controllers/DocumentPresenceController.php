<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentPresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentPresenceController extends Controller
{
    public function __construct(private readonly DocumentPresenceService $presence)
    {
    }

    /**
     * Keep the user's session alive on a document.
     * Called every 30 seconds via JS fetch().
     */
    public function heartbeat(Request $request, Document $document): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['ok' => false], 401);
        }

        $this->presence->heartbeat($document, $user);

        // Return compact list of who else is active (for optional UI refresh)
        $sessions = $this->presence->getActiveSessions($document)
            ->map(fn ($s) => [
                'name' => $s->user?->name ?? '—',
                'mode' => $s->mode,
                'mine' => $s->user_id === $user->id,
            ]);

        return response()->json(['ok' => true, 'sessions' => $sessions]);
    }

    /**
     * Remove the user's session when they leave the page.
     * Called via navigator.sendBeacon() on beforeunload.
     */
    public function leave(Request $request, Document $document): JsonResponse
    {
        $user = Auth::user();

        if ($user) {
            $this->presence->leave($document, $user);
        }

        return response()->json(['ok' => true]);
    }
}
