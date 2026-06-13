<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\DocumentShare;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies the share owner when an external collaborator
 * opens the shared link for the first time.
 *
 * Sent only once (on first access: access_count transitions 0 → 1).
 * For subsequent accesses the controller increments the counter silently.
 */
class ShareAccessedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Document      $document,
        private readonly DocumentShare $share,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Accès document partagé : ' . $this->document->titre)
            ->greeting('Bonjour,')
            ->line('Votre lien de partage pour le document « ' . $this->document->titre . ' » vient d\'être consulté.')
            ->line('Destinataire : ' . ($this->share->recipient_email ?? '—'))
            ->line('Date : ' . now()->format('d/m/Y à H:i'))
            ->action('Voir les détails du partage', route('filament.admin.resources.documents.view', $this->document->id))
            ->line('Vous pouvez révoquer ce partage depuis la fiche du document.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'            => 'Lien consulté : ' . $this->document->titre,
            'body'             => 'Votre lien partagé avec « ' . ($this->share->recipient_email ?? 'externe') . ' » a été consulté pour la première fois.',
            'document_id'      => $this->document->id,
            'share_id'         => $this->share->id,
            'recipient_email'  => $this->share->recipient_email,
            'url'              => route('filament.admin.resources.documents.view', $this->document->id),
        ];
    }
}
