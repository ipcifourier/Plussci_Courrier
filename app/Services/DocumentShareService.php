<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentAccessRule;
use App\Models\DocumentShare;
use App\Models\User;
use App\Notifications\DocumentSharedNotification;
use App\Services\AuditLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Notifications\AnonymousNotifiable;

class DocumentShareService
{
    /**
     * Partage un document avec un utilisateur interne.
     * Crée un DocumentShare + une DocumentAccessRule + envoie une notification.
     */
    public function shareWithUser(
        Document $document,
        User     $recipient,
        User     $sharedBy,
        bool     $canDownload = false,
        bool     $canComment  = false,
        bool     $canEdit     = false,
        ?Carbon  $expiresAt   = null,
    ): DocumentShare {
        $share = DocumentShare::create([
            'document_id'        => $document->id,
            'shared_by_id'       => $sharedBy->id,
            'recipient_user_id'  => $recipient->id,
            'type'               => 'internal',
            'can_view'           => true,
            'can_download'       => $canDownload,
            'can_comment'        => $canComment,
            'can_edit'           => $canEdit,
            'expires_at'         => $expiresAt,
        ]);

        // Crée (ou met à jour) la règle d'accès associée
        DocumentAccessRule::updateOrCreate(
            ['document_id' => $document->id, 'user_id' => $recipient->id],
            [
                'can_view'     => true,
                'can_download' => $canDownload,
                'can_edit'     => $canEdit,
                'can_share'    => false,
            ]
        );

        $recipient->notify(new DocumentSharedNotification($document, $share, $sharedBy->name));

        app(AuditLogger::class)->log(
            action: 'documents.share.created',
            entity: $document,
            meta: [
                'share_id' => $share->id,
                'type' => 'internal',
                'recipient_user_id' => $recipient->id,
                'can_download' => $canDownload,
                'can_comment' => $canComment,
                'can_edit' => $canEdit,
                'expires_at' => $expiresAt?->toIso8601String(),
            ],
        );

        return $share;
    }

    /**
     * Partage un document avec un collaborateur externe (par e-mail).
     * Crée un DocumentShare avec token + envoie un e-mail.
     */
    public function shareWithEmail(
        Document $document,
        string   $recipientEmail,
        User     $sharedBy,
        bool     $canDownload = false,
        bool     $canComment  = false,
        bool     $canEdit     = false,
        ?Carbon  $expiresAt   = null,
    ): DocumentShare {
        $share = DocumentShare::create([
            'document_id'      => $document->id,
            'shared_by_id'     => $sharedBy->id,
            'recipient_email'  => strtolower(trim($recipientEmail)),
            'token'            => DocumentShare::generateToken(),
            'type'             => 'external',
            'can_view'         => true,
            'can_download'     => $canDownload,
            'can_comment'      => $canComment,
            'can_edit'         => $canEdit,
            'expires_at'       => $expiresAt,
        ]);

        // Notifie via e-mail l'adresse externe (sans compte utilisateur)
        $notifiable = (new AnonymousNotifiable)->route('mail', $recipientEmail);
        $notifiable->notify(new DocumentSharedNotification($document, $share, $sharedBy->name));

        app(AuditLogger::class)->log(
            action: 'documents.share.created',
            entity: $document,
            meta: [
                'share_id' => $share->id,
                'type' => 'external',
                'recipient_email' => $share->recipient_email,
                'can_download' => $canDownload,
                'can_comment' => $canComment,
                'can_edit' => $canEdit,
                'expires_at' => $expiresAt?->toIso8601String(),
            ],
        );

        return $share;
    }

    /**
     * Révoque un partage (le lien devient inaccessible immédiatement).
     */
    public function revoke(DocumentShare $share): void
    {
        $share->update(['revoked_at' => now()]);

        app(AuditLogger::class)->log(
            action: 'documents.share.revoked',
            entity: $share->document,
            meta: [
                'share_id' => $share->id,
                'type' => $share->type,
            ],
        );
    }

    /**
     * Révoque un partage interne ET supprime la DocumentAccessRule associée.
     * Utile pour retirer immédiatement l'accès d'un collaborateur interne.
     */
    public function revokeWithAccessRule(DocumentShare $share): void
    {
        $this->revoke($share);

        if ($share->isInternal() && $share->recipient_user_id) {
            DocumentAccessRule::where('document_id', $share->document_id)
                ->where('user_id', $share->recipient_user_id)
                ->delete();
        }
    }

    /**
     * Prolonge la date d'expiration d'un partage existant.
     * Si $newExpiry est null, supprime toute expiration (accès permanent).
     */
    public function extendExpiry(DocumentShare $share, ?Carbon $newExpiry): void
    {
        $share->update(['expires_at' => $newExpiry]);

        app(AuditLogger::class)->log(
            action: 'documents.share.expiry_updated',
            entity: $share->document,
            meta: [
                'share_id' => $share->id,
                'expires_at' => $newExpiry?->toIso8601String(),
            ],
        );
    }

    /**
     * Révoque tous les partages actifs d'un document pour un destinataire donné
     * (interne uniquement) et supprime la règle d'accès correspondante.
     */
    public function revokeAllForRecipient(Document $document, User $recipient): int
    {
        $shares = DocumentShare::where('document_id', $document->id)
            ->where('recipient_user_id', $recipient->id)
            ->whereNull('revoked_at')
            ->get();

        foreach ($shares as $share) {
            $share->update(['revoked_at' => now()]);
        }

        DocumentAccessRule::where('document_id', $document->id)
            ->where('user_id', $recipient->id)
            ->delete();

        return $shares->count();
    }

    /**
     * Retourne l'URL de partage pour un partage externe.
     */
    public function getShareUrl(DocumentShare $share): ?string
    {
        if (! $share->isExternal() || ! $share->token) {
            return null;
        }

        return route('share.show', $share->token);
    }
}
