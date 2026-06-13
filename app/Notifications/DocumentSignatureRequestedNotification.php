<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentSignatureRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Document $document)
    {
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
            ->subject('Parapheur — Signature requise : ' . $this->document->titre)
            ->line("Le document « {$this->document->titre} » ({$this->document->reference_doc}) attend votre signature.")
            ->action('Voir le document', route('filament.admin.resources.documents.view', $this->document));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'       => 'Signature requise',
            'body'        => "Le document « {$this->document->titre} » attend votre signature (niveau {$this->document->current_signature_level}).",
            'document_id' => $this->document->id,
            'url'         => route('filament.admin.resources.documents.view', $this->document),
        ];
    }
}
