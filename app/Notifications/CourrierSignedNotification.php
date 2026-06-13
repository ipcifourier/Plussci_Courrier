<?php

namespace App\Notifications;

use App\Models\Courrier;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CourrierSignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Courrier $courrier,
        private readonly User $signer,
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

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Courrier signé',
            'body' => "Le courrier {$this->courrier->reference} a été signé par {$this->signer->name}.",
            'courrier_id' => $this->courrier->id,
            'url' => route('filament.admin.resources.courriers.view', $this->courrier),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Courrier signé - ' . $this->courrier->reference)
            ->line("Le courrier {$this->courrier->reference} a été signé par {$this->signer->name}.")
            ->action('Voir le courrier', route('filament.admin.resources.courriers.view', $this->courrier));
    }
}
