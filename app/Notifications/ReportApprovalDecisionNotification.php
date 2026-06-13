<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportApprovalDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Report $report,
        private readonly string $decision,
        private readonly ?string $comment = null,
    ) {
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
        $subjectPrefix = $this->decision === 'approved' ? 'Approuve' : 'Rejete';

        $mail = (new MailMessage)
            ->subject("{$subjectPrefix} - Rapport {$this->report->reference}")
            ->line($this->decision === 'approved'
                ? "Le rapport {$this->report->reference} a ete entierement approuve."
                : "Le rapport {$this->report->reference} a ete rejete.");

        if ($this->comment) {
            $mail->line('Motif: ' . $this->comment);
        }

        return $mail->action('Voir le rapport', route('filament.admin.resources.reports.view', $this->report));
    }

    public function toArray(object $notifiable): array
    {
        $body = $this->decision === 'approved'
            ? "Le rapport {$this->report->reference} a ete entierement approuve."
            : "Le rapport {$this->report->reference} a ete rejete.";

        if ($this->comment) {
            $body .= " Motif: {$this->comment}";
        }

        return [
            'title' => 'Decision de validation',
            'body' => $body,
            'decision' => $this->decision,
            'report_id' => $this->report->id,
            'url' => route('filament.admin.resources.reports.view', $this->report),
        ];
    }
}
