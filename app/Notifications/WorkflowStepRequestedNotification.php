<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\DocumentWorkflow;
use App\Models\DocumentWorkflowStep;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies the approver that a workflow step is waiting for their decision.
 */
class WorkflowStepRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Document             $document,
        private readonly DocumentWorkflow     $workflow,
        private readonly DocumentWorkflowStep $step,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Action requise — ' . $this->step->actionLabel() . ' : ' . $this->document->titre)
            ->greeting('Bonjour ' . ($notifiable->name ?? '') . ',')
            ->line('Le document « ' . $this->document->titre . ' » (réf. ' . ($this->document->reference_doc ?? '—') . ') attend votre ' . mb_strtolower($this->step->actionLabel()) . '.')
            ->line('Étape ' . $this->step->step_order . ' : **' . $this->step->label . '**')
            ->line('Circuit : ' . $this->workflow->template_name)
            ->action('Voir le document', route('filament.admin.resources.documents.view', $this->document->id))
            ->line('Merci de traiter cette demande dès que possible.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'       => $this->step->actionLabel() . ' requise — ' . $this->document->titre,
            'body'        => 'Étape ' . $this->step->step_order . ' « ' . $this->step->label . ' » du circuit « ' . $this->workflow->template_name . ' » attend votre décision.',
            'document_id' => $this->document->id,
            'workflow_id' => $this->workflow->id,
            'step_id'     => $this->step->id,
            'url'         => route('filament.admin.resources.documents.view', $this->document->id),
        ];
    }
}
