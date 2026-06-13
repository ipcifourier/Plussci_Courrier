<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentAccessRule;
use App\Models\User;

/**
 * Fine-grained per-document access control.
 *
 * Logic:
 *  • If NO access rules exist for a document → fall through to global
 *    Spatie permissions (backward-compatible open access).
 *  • If rules EXIST → the user must match at least one rule with the
 *    relevant boolean flag set to true.
 *
 * The policy `before()` hook already grants Super Admin unconditional access,
 * so this service never receives super-admin calls.
 */
class DocumentAccessService
{
    // ── Public helpers ────────────────────────────────────────────────────────

    /**
     * Can the user VIEW this document?
     * Requires global `ged.documents.view` + optional per-doc ACL.
     */
    public function canView(User $user, Document $document): bool
    {
        if ($document->dossier && ! $document->dossier->isVisibleTo($user)) {
            return false;
        }

        if (! $user->can('ged.documents.view')) {
            // No global permission → only an explicit rule can grant
            return $this->matchesRule($user, $document, 'can_view');
        }

        return $this->passesAccessRuleIfAny($user, $document, 'can_view');
    }

    /**
     * Can the user EDIT (update) this document?
     */
    public function canEdit(User $user, Document $document): bool
    {
        if ($document->dossier && ! $document->dossier->isVisibleTo($user)) {
            return false;
        }

        if ($document->isReadOnlyFinalized() && ! $user->can('admin.roles.manage')) {
            return false;
        }

        if (! $user->can('ged.documents.update')) {
            return $this->matchesRule($user, $document, 'can_edit');
        }

        return $this->passesAccessRuleIfAny($user, $document, 'can_edit');
    }

    /**
     * Can the user DOWNLOAD files attached to this document?
     */
    public function canDownload(User $user, Document $document): bool
    {
        if ($document->dossier && ! $document->dossier->isVisibleTo($user)) {
            return false;
        }

        if (! $user->can('ged.documents.view')) {
            return $this->matchesRule($user, $document, 'can_download');
        }

        return $this->passesAccessRuleIfAny($user, $document, 'can_download');
    }

    /**
     * Can the user SHARE this document?
     */
    public function canShare(User $user, Document $document): bool
    {
        if ($document->dossier && ! $document->dossier->isVisibleTo($user)) {
            return false;
        }

        // Sharing always requires an explicit rule
        return $this->matchesRule($user, $document, 'can_share');
    }

    /**
     * Can the user DELETE this document?
     * Deletion only uses global Spatie permission (no per-doc ACL for delete).
     */
    public function canDelete(User $user, Document $document): bool
    {
        return $user->can('ged.documents.delete');
    }

    // ── Internal helpers ──────────────────────────────────────────────────────

    /**
     * If NO rules exist for the document, return true (open access).
     * If rules exist, the user must match at least one with the ability set.
     */
    private function passesAccessRuleIfAny(User $user, Document $document, string $ability): bool
    {
        $rules = $document->accessRules()->where($ability, true)->get();

        if ($rules->isEmpty()) {
            // No restrictions configured → global permission is enough
            return true;
        }

        return $rules->contains(fn (DocumentAccessRule $rule) => $rule->matchesUser($user));
    }

    /**
     * Does the user match ANY rule on this document with the ability = true?
     * Used when the user lacks a global permission.
     */
    private function matchesRule(User $user, Document $document, string $ability): bool
    {
        return $document->accessRules()
            ->where($ability, true)
            ->get()
            ->contains(fn (DocumentAccessRule $rule) => $rule->matchesUser($user));
    }
}
