<?php

namespace App\Notifications;

use App\Models\Courrier;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CourrierApprovalRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Courrier $courrier)
    {
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
        return (new MailMessage)
            ->subject('Validation requise - Courrier ' . $this->courrier->reference)
            ->line("Le courrier {$this->courrier->reference} attend votre approbation.")
            ->action('Voir le courrier', route('filament.admin.resources.courriers.view', $this->courrier));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Validation requise',
            'body' => "Le courrier {$this->courrier->reference} attend votre approbation.",
            'courrier_id' => $this->courrier->id,
            'url' => route('filament.admin.resources.courriers.view', $this->courrier),
        ];
    }
}
