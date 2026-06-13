<?php

namespace App\Http\Controllers;

use App\Models\DocumentShare;
use App\Notifications\ShareAccessedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DocumentShareController extends Controller
{
    /**
     * Affiche la page publique d'accès au document partagé.
     */
    public function show(string $token): View|RedirectResponse
    {
        $share = DocumentShare::where('token', $token)
            ->with(['document', 'sharedBy'])
            ->first();

        if (! $share) {
            abort(404, 'Lien de partage introuvable.');
        }

        if ($share->isRevoked()) {
            abort(410, 'Ce lien de partage a été révoqué.');
        }

        if ($share->isExpired()) {
            abort(410, 'Ce lien de partage a expiré.');
        }

        $isFirstAccess = $share->access_count === 0;

        $share->recordAccess();

        // Notify the share owner on first access (external shares only)
        if ($isFirstAccess && $share->isExternal() && $share->sharedBy) {
            $share->sharedBy->notify(new ShareAccessedNotification($share->document, $share));
        }

        $document   = $share->document;
        $mediaItems = $document->getMedia('documents');

        return view('share.show', compact('share', 'document', 'mediaItems'));
    }

    /**
     * Télécharge le fichier associé si le partage en donne le droit.
     */
    public function download(string $token, int $mediaId): RedirectResponse
    {
        $share = DocumentShare::where('token', $token)->first();

        if (! $share || ! $share->isValid()) {
            abort(410, 'Ce lien de partage n\'est plus valide.');
        }

        if (! $share->can_download) {
            abort(403, 'Ce partage ne permet pas le téléchargement.');
        }

        $media = $share->document->getMedia('documents')->firstWhere('id', $mediaId);

        if (! $media) {
            abort(404);
        }

        $share->recordAccess();

        return redirect($media->getUrl());
    }
}
