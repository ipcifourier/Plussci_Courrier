<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentEditedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Document $document,
        private readonly User     $editedBy,
    ) {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database', 'broadcast'];
        if (config('courriers.email_notifications') && filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Document modifié')
            ->line($this->editedBy->name . ' vient de modifier le document « ' . $this->document->titre . ' ».')
            ->action('Se connecter', route('filament.admin.auth.login'))
            ->line('Après connexion, ouvrez : ' . route('filament.admin.resources.documents.view', $this->document->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'       => 'Document modifié',
            'body'        => $this->editedBy->name . ' vient de modifier « ' . $this->document->titre . ' ».',
            'document_id' => $this->document->id,
            'edited_by'   => $this->editedBy->id,
            'url'         => route('filament.admin.resources.documents.view', $this->document->id),
        ];
    }
}
