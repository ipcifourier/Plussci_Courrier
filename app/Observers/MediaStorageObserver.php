<?php

namespace App\Observers;

use App\Models\Document;
use App\Models\User;
use App\Services\StorageQuotaService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaStorageObserver
{
    public function __construct(private readonly StorageQuotaService $quota)
    {
    }

    /**
     * Reject the upload if the document's author would exceed their quota.
     */
    public function creating(Media $media): void
    {
        // Only enforce quota for GED document media
        if ($media->model_type !== Document::class) {
            return;
        }

        $document = Document::find($media->model_id);

        if (! $document) {
            return;
        }

        $user = User::find($document->auteur_id);

        if (! $user) {
            return;
        }

        if (! $this->quota->canUpload($user, (int) $media->size)) {
            $usedMb  = $this->quota->getUsedMb($user);
            $quotaMb = $this->quota->getQuotaMb();

            throw new \RuntimeException(
                "Quota de stockage dépassé pour {$user->name}. "
                . "Utilisé : {$usedMb} Mo / {$quotaMb} Mo."
            );
        }
    }
}
