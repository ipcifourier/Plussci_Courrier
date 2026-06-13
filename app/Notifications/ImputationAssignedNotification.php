<?php

namespace App\Notifications;

use App\Models\Imputation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ImputationAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Imputation $imputation)
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
        $payload = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject('Nouvelle imputation reçue')
            ->line($payload['body'])
            ->action('Se connecter', route('filament.admin.auth.login'))
            ->line('Après connexion, ouvrez : ' . ($payload['url'] ?? route('filament.admin.resources.courriers.index')));
    }

    public function toArray(object $notifiable): array
    {
        $courrier = $this->imputation->courrier;

        return [
            'title' => 'Nouvelle imputation reçue',
            'body' => sprintf(
                'Le courrier %s vous a été imputé par %s.',
                $courrier?->reference ?? ('#' . $this->imputation->courrier_id),
                $this->imputation->expediteur?->name ?? 'un agent'
            ),
            'courrier_id' => $this->imputation->courrier_id,
            'imputation_id' => $this->imputation->id,
            'url' => route('filament.admin.resources.courriers.view', $this->imputation->courrier_id),
        ];
    }
}
