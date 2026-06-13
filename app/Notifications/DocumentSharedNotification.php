<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\DocumentShare;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentSharedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Document      $document,
        private readonly DocumentShare $share,
        private readonly string        $sharedByName,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = $this->share->isExternal()
            ? $this->share->shareUrl()
            : route('filament.admin.resources.documents.view', $this->document->id);

        $message = (new MailMessage)
            ->subject('Document partagé : ' . $this->document->titre)
            ->greeting('Bonjour,')
            ->line($this->sharedByName . ' vous a partagé le document « ' . $this->document->titre . ' ».')
            ->line('Référence : ' . ($this->document->reference_doc ?? '—'))
            ->line('Permissions : ' . $this->permissionsLabel());

        if ($this->share->expires_at) {
            $message->line('Ce lien expire le ' . $this->share->expires_at->format('d/m/Y à H:i') . '.');
        }

        $message->action('Accéder au document', $url);

        return $message;
    }

    public function toArray(object $notifiable): array
    {
        $url = $this->share->isExternal()
            ? $this->share->shareUrl()
            : route('filament.admin.resources.documents.view', $this->document->id);

        return [
            'title'       => 'Document partagé : ' . $this->document->titre,
            'body'        => $this->sharedByName . ' vous a partagé « ' . $this->document->titre . ' ». ' . $this->permissionsLabel(),
            'document_id' => $this->document->id,
            'share_id'    => $this->share->id,
            'url'         => $url,
        ];
    }

    private function permissionsLabel(): string
    {
        $perms = [];
        if ($this->share->can_view)     $perms[] = 'consultation';
        if ($this->share->can_download) $perms[] = 'téléchargement';
        if ($this->share->can_comment)  $perms[] = 'commentaires';

        return 'Droits : ' . (empty($perms) ? '—' : implode(', ', $perms));
    }
}
