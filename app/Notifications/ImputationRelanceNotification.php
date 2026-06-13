<?php

namespace App\Notifications;

use App\Models\Imputation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ImputationRelanceNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Imputation $imputation)
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
        $payload = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject('Relance : imputation en retard')
            ->line($payload['body'])
            ->action('Se connecter', route('filament.admin.auth.login'))
            ->line('Après connexion, ouvrez : ' . ($payload['url'] ?? route('filament.admin.resources.courriers.index')));
    }

    public function toArray(object $notifiable): array
    {
        $courrier = $this->imputation->courrier;
        $ref      = $courrier?->reference ?? '—';
        $due      = $this->imputation->delai_traitement?->format('d/m/Y');

        $body = sprintf(
            'L\'imputation "%s" sur le courrier "%s" est en retard (délai : %s). Veuillez la traiter.',
            $this->imputation->instructions ?? 'Sans objet',
            $ref,
            $due
        );

        $url = $courrier
            ? route('filament.admin.resources.courriers.view', $courrier->id)
            : route('filament.admin.resources.courriers.index');

        return [
            'title'          => 'Relance : imputation en retard',
            'body'           => $body,
            'imputation_id'  => $this->imputation->id,
            'courrier_id'    => $courrier?->id,
            'url'            => $url,
        ];
    }
}
