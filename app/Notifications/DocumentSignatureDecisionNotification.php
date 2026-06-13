<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentSignatureDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Document $document,
        private readonly string $decision,
        private readonly ?string $comment = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (config('courriers.email_notifications') && filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = $this->decision === 'completed' ? 'approuvé' : 'rejeté';

        $mail = (new MailMessage)
            ->subject("Parapheur {$label} — {$this->document->titre}")
            ->line("Le circuit de signature du document « {$this->document->titre} » a été {$label}.");

        if ($this->comment) {
            $mail->line("Commentaire : {$this->comment}");
        }

        $mail->action('Voir le document', route('filament.admin.resources.documents.view', $this->document));

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        $label = $this->decision === 'completed' ? 'approuvé' : 'rejeté';

        return [
            'title'       => 'Parapheur ' . $label,
            'body'        => "Le circuit du document « {$this->document->titre} » a été {$label}." . ($this->comment ? " — {$this->comment}" : ''),
            'document_id' => $this->document->id,
            'url'         => route('filament.admin.resources.documents.view', $this->document),
        ];
    }
}
