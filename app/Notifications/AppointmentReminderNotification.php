<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** A1 — Rappel d'un rendez-vous à venir. */
class AppointmentReminderNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Appointment $appointment, private readonly string $window = 'J-1')
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
        $date = $this->appointment->starts_at->format('d/m/Y à H:i');

        return (new MailMessage)
            ->subject("Rappel RDV ({$this->window}) — {$this->appointment->title}")
            ->line("Vous avez un rendez-vous prévu : **{$this->appointment->title}**")
            ->line("Date : {$date}")
            ->line("Lieu : " . ($this->appointment->location ?? 'Non précisé'))
            ->action('Voir le rendez-vous', route('filament.admin.resources.appointments.index'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'          => "Rappel RDV ({$this->window})",
            'body'           => "{$this->appointment->title} — " . $this->appointment->starts_at->format('d/m/Y H:i'),
            'appointment_id' => $this->appointment->id,
            'url'            => route('filament.admin.resources.appointments.index'),
        ];
    }
}
