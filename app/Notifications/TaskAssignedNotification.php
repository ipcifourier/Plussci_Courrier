<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Task $task)
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
        $assigner = $this->task->assigner?->name ?? 'Un utilisateur';
        $due = $this->task->due_date?->format('d/m/Y');
        $url = $this->toArray($notifiable)['url'] ?? route('filament.admin.resources.tasks.index');

        return (new MailMessage)
            ->subject('Nouvelle tâche assignée')
            ->line($assigner . ' vous a assigné la tâche "' . $this->task->titre . '"' . ($due ? ' (échéance : ' . $due . ')' : '') . '.')
            ->action('Se connecter', route('filament.admin.auth.login'))
            ->line('Après connexion, ouvrez : ' . $url);
    }

    public function toArray(object $notifiable): array
    {
        $assigner = $this->task->assigner?->name ?? 'Un utilisateur';
        $due      = $this->task->due_date?->format('d/m/Y');

        $body = sprintf(
            '%s vous a assigné la tâche "%s"%s.',
            $assigner,
            $this->task->titre,
            $due ? ' (échéance : ' . $due . ')' : ''
        );

        // Build a URL toward the parent resource if available
        $taskable = $this->task->taskable;
        $url = match (true) {
            $taskable instanceof \App\Models\Courrier => route('filament.admin.resources.courriers.view', $taskable->id),
            $taskable instanceof \App\Models\Document => route('filament.admin.resources.documents.view', $taskable->id),
            default                                   => route('filament.admin.resources.tasks.index'),
        };

        return [
            'title'   => 'Nouvelle tâche assignée',
            'body'    => $body,
            'task_id' => $this->task->id,
            'url'     => $url,
        ];
    }
}
