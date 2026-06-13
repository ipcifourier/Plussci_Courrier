<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Notification envoyée lorsqu'un rendez-vous/diligence est assigné à un utilisateur. */
class AppointmentAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Appointment $appointment)
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
        $date  = $this->appointment->starts_at->format('d/m/Y à H:i');
        $label = $this->appointment->type === 'diligence' ? 'Diligence' : 'Rendez-vous';

        return (new MailMessage)
            ->subject("{$label} assigné — {$this->appointment->title}")
            ->line("Un {$label} vous a été assigné : **{$this->appointment->title}**")
            ->line("Date : {$date}")
            ->line("Lieu : " . ($this->appointment->location ?? 'Non précisé'))
            ->action("Voir le {$label}", route('filament.admin.resources.appointments.index'));
    }

    public function toArray(object $notifiable): array
    {
        $label = $this->appointment->type === 'diligence' ? 'Diligence' : 'Rendez-vous';

        return [
            'title'          => "{$label} assigné",
            'body'           => "{$this->appointment->title} — " . $this->appointment->starts_at->format('d/m/Y H:i'),
            'appointment_id' => $this->appointment->id,
            'url'            => route('filament.admin.resources.appointments.index'),
        ];
    }
}
