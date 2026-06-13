<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use App\Services\DocumentAccessService;

class DocumentPolicy
{
    public function __construct(private DocumentAccessService $access) {}

    public function before(User $user, string $ability): bool | null
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('ged.documents.view');
    }

    public function view(User $user, Document $document): bool
    {
        return $this->access->canView($user, $document);
    }

    public function create(User $user): bool
    {
        return $user->can('ged.documents.create');
    }

    public function update(User $user, Document $document): bool
    {
        return $this->access->canEdit($user, $document);
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->access->canDelete($user, $document);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('ged.documents.delete');
    }
}
