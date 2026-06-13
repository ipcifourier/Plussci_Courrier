<?php

namespace App\Notifications;

use App\Models\Courrier;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CourrierApprovalDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Courrier $courrier,
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
        $subjectPrefix = $this->decision === 'approved' ? 'Approuvé' : 'Rejeté';

        $mail = (new MailMessage)
            ->subject("{$subjectPrefix} - Courrier {$this->courrier->reference}")
            ->line($this->decision === 'approved'
                ? "Le courrier {$this->courrier->reference} a été entièrement approuvé."
                : "Le courrier {$this->courrier->reference} a été rejeté.");

        if ($this->comment) {
            $mail->line('Motif: ' . $this->comment);
        }

        return $mail->action('Voir le courrier', route('filament.admin.resources.courriers.view', $this->courrier));
    }

    public function toArray(object $notifiable): array
    {
        $body = $this->decision === 'approved'
            ? "Le courrier {$this->courrier->reference} a été entièrement approuvé."
            : "Le courrier {$this->courrier->reference} a été rejeté.";

        if ($this->comment) {
            $body .= " Motif: {$this->comment}";
        }

        return [
            'title' => 'Décision de validation',
            'body' => $body,
            'decision' => $this->decision,
            'courrier_id' => $this->courrier->id,
            'url' => route('filament.admin.resources.courriers.view', $this->courrier),
        ];
    }
}
