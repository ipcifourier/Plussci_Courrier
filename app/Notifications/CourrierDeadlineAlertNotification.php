<?php

namespace App\Notifications;

use App\Models\Courrier;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * C2 — Alerte d'échéance courrier imminente.
 */
class CourrierDeadlineAlertNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Courrier $courrier, private readonly int $daysLeft)
    {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label   = $this->daysLeft === 0 ? "aujourd'hui" : "dans {$this->daysLeft} jour(s)";
        $deadline = $this->courrier->delai_reponse?->format('d/m/Y') ?? '-';

        return (new MailMessage)
            ->subject("⚠ Échéance courrier : {$this->courrier->objet}")
            ->line("Le courrier **{$this->courrier->reference}** expire {$label} (échéance : {$deadline}).")
            ->line("Objet : {$this->courrier->objet}")
            ->action('Voir le courrier', route('filament.admin.resources.courriers.index'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'       => "Échéance courrier ({$this->daysLeft}j)",
            'body'        => "{$this->courrier->reference} — {$this->courrier->objet}",
            'courrier_id' => $this->courrier->id,
            'url'         => route('filament.admin.resources.courriers.index'),
        ];
    }
}
