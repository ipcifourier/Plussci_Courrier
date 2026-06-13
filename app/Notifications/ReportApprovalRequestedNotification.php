<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportApprovalRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Report $report)
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
            ->subject('Validation requise - Rapport ' . $this->report->reference)
            ->line("Le rapport {$this->report->reference} attend votre approbation.")
            ->action('Voir le rapport', route('filament.admin.resources.reports.view', $this->report));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Validation requise',
            'body' => "Le rapport {$this->report->reference} attend votre approbation.",
            'report_id' => $this->report->id,
            'url' => route('filament.admin.resources.reports.view', $this->report),
        ];
    }
}
