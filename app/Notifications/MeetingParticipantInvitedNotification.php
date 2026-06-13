<?php

namespace App\Notifications;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Notification envoyée aux participants lors de la création d'une réunion. */
class MeetingParticipantInvitedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Meeting $meeting)
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
        $date = $this->meeting->starts_at->format('d/m/Y à H:i');

        return (new MailMessage)
            ->subject("Invitation à une réunion — {$this->meeting->title}")
            ->line("Vous avez été invité à la réunion : **{$this->meeting->title}**")
            ->line("Date : {$date}")
            ->line("Lieu : " . ($this->meeting->location ?? 'Non précisé'))
            ->action('Voir la réunion', route('filament.admin.resources.meetings.index'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'      => 'Invitation à une réunion',
            'body'       => "{$this->meeting->title} — " . $this->meeting->starts_at->format('d/m/Y H:i'),
            'meeting_id' => $this->meeting->id,
            'url'        => route('filament.admin.resources.meetings.index'),
        ];
    }
}
