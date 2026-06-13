<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskDeadlineReminderNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Task $task)
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
            ->subject($payload['title'])
            ->line($payload['body'])
            ->action('Se connecter', route('filament.admin.auth.login'))
            ->line('Après connexion, ouvrez : ' . ($payload['url'] ?? route('filament.admin.resources.tasks.index')));
    }

    public function toArray(object $notifiable): array
    {
        $due     = $this->task->due_date?->format('d/m/Y');
        $overdue = $this->task->isOverdue();

        if ($overdue) {
            $body = sprintf(
                'La tâche "%s" est en retard (échéance : %s). Veuillez la traiter dès que possible.',
                $this->task->titre,
                $due
            );
            $title = 'Tâche en retard';
        } else {
            $body = sprintf(
                'La tâche "%s" arrive à échéance le %s. Pensez à la traiter.',
                $this->task->titre,
                $due
            );
            $title = 'Rappel d\'échéance';
        }

        $taskable = $this->task->taskable;
        $url = match (true) {
            $taskable instanceof \App\Models\Courrier => route('filament.admin.resources.courriers.view', $taskable->id),
            $taskable instanceof \App\Models\Document => route('filament.admin.resources.documents.view', $taskable->id),
            default                                   => route('filament.admin.resources.tasks.index'),
        };

        return [
            'title'   => $title,
            'body'    => $body,
            'task_id' => $this->task->id,
            'url'     => $url,
        ];
    }
}
