<?php

namespace App\Services;

use App\Models\User;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class StorageQuotaService
{
    /**
     * Return the configured quota in bytes.
     */
    public function getQuotaBytes(): int
    {
        return $this->getQuotaMb() * 1024 * 1024;
    }

    /**
     * Return the configured quota in megabytes.
     */
    public function getQuotaMb(): int
    {
        return app(GedSettingsService::class)->uploadQuotaMb();
    }

    /**
     * Return total bytes consumed by document media owned by a user.
     */
    public function getUsedBytes(User $user): int
    {
        return (int) Media::query()
            ->where('model_type', \App\Models\Document::class)
            ->whereHasMorph(
                'model',
                [\App\Models\Document::class],
                fn ($q) => $q->where('auteur_id', $user->id)
            )
            ->sum('size');
    }

    /**
     * Return total megabytes consumed (rounded to 2 decimals).
     */
    public function getUsedMb(User $user): float
    {
        return round($this->getUsedBytes($user) / 1024 / 1024, 2);
    }

    /**
     * Return remaining bytes for this user.
     */
    public function getRemainingBytes(User $user): int
    {
        return max(0, $this->getQuotaBytes() - $this->getUsedBytes($user));
    }

    /**
     * Whether a user can upload a file of given size (in bytes).
     */
    public function canUpload(User $user, int $fileSizeBytes): bool
    {
        return $fileSizeBytes <= $this->getRemainingBytes($user);
    }
}
