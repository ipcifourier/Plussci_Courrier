<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\DocumentWorkflow;
use App\Models\DocumentWorkflowStep;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkflowStepEscalatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Document $document,
        private readonly DocumentWorkflow $workflow,
        private readonly DocumentWorkflowStep $step,
        private readonly bool $isEscalationRecipient = false,
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
        $payload = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject($payload['title'])
            ->greeting('Bonjour ' . ($notifiable->name ?? '') . ',')
            ->line($payload['body'])
            ->action('Voir le document', route('filament.admin.resources.documents.view', $this->document->id));
    }

    public function toArray(object $notifiable): array
    {
        $deadline = $this->step->deadlineAt()?->format('d/m/Y H:i') ?? '—';

        $title = $this->isEscalationRecipient
            ? 'Escalade workflow — étape en retard'
            : 'Rappel urgent — étape workflow en retard';

        $body = $this->isEscalationRecipient
            ? 'Le document « ' . $this->document->titre . ' » est en retard sur l\'étape « ' . $this->step->label . ' » (échéance : ' . $deadline . ').'
            : 'Votre étape « ' . $this->step->label . ' » est en retard pour le document « ' . $this->document->titre . ' » (échéance : ' . $deadline . ').';

        return [
            'title' => $title,
            'body' => $body,
            'document_id' => $this->document->id,
            'workflow_id' => $this->workflow->id,
            'step_id' => $this->step->id,
            'due_at' => $deadline,
            'is_escalation_recipient' => $this->isEscalationRecipient,
            'url' => route('filament.admin.resources.documents.view', $this->document->id),
        ];
    }
}
