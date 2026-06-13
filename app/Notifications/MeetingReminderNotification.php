<?php

namespace App\Notifications;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** A1 — Rappel d'une réunion à venir. */
class MeetingReminderNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Meeting $meeting, private readonly string $window = 'J-1')
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
            ->subject("Rappel réunion ({$this->window}) — {$this->meeting->title}")
            ->line("Vous participez à la réunion : **{$this->meeting->title}**")
            ->line("Date : {$date}")
            ->line("Lieu : " . ($this->meeting->location ?? 'Non précisé'))
            ->action('Voir la réunion', route('filament.admin.resources.meetings.index'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'      => "Rappel réunion ({$this->window})",
            'body'       => "{$this->meeting->title} — " . $this->meeting->starts_at->format('d/m/Y H:i'),
            'meeting_id' => $this->meeting->id,
            'url'        => route('filament.admin.resources.meetings.index'),
        ];
    }
}
