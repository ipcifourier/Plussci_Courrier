<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\DocumentWorkflow;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies the workflow initiator (and the document author if different)
 * that the workflow has reached a terminal state.
 */
class WorkflowCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Document         $document,
        private readonly DocumentWorkflow $workflow,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $outcome = $this->workflow->isApproved() ? 'approuvé' : ($this->workflow->isRejected() ? 'rejeté' : 'annulé');

        return (new MailMessage)
            ->subject('Circuit terminé — ' . $this->document->titre . ' : ' . ucfirst($outcome))
            ->greeting('Bonjour ' . ($notifiable->name ?? '') . ',')
            ->line('Le circuit « ' . $this->workflow->template_name . ' » pour le document « ' . $this->document->titre . ' » est terminé.')
            ->line('Résultat : **' . ucfirst($outcome) . '**')
            ->when($this->workflow->final_comment, fn ($msg) => $msg->line('Commentaire : ' . $this->workflow->final_comment))
            ->action('Voir le document', route('filament.admin.resources.documents.view', $this->document->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'       => 'Circuit ' . $this->workflow->statusLabel() . ' — ' . $this->document->titre,
            'body'        => 'Le circuit « ' . $this->workflow->template_name . ' » est terminé : ' . $this->workflow->statusLabel() . '.',
            'document_id' => $this->document->id,
            'workflow_id' => $this->workflow->id,
            'status'      => $this->workflow->status,
            'url'         => route('filament.admin.resources.documents.view', $this->document->id),
        ];
    }
}
