<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MentionNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Comment $comment)
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
            ->subject('Vous avez été mentionné')
            ->line($payload['body'])
            ->action('Se connecter', route('filament.admin.auth.login'))
            ->line('Après connexion, ouvrez : ' . ($payload['url'] ?? url('/admin')));
    }

    public function toArray(object $notifiable): array
    {
        $commentable = $this->comment->commentable;
        $author      = $this->comment->user?->name ?? 'Un utilisateur';

        // Build a label for the parent resource
        $resourceLabel = match (true) {
            $commentable instanceof \App\Models\Courrier  => 'courrier ' . ($commentable->reference ?? '#' . $commentable->id),
            $commentable instanceof \App\Models\Document  => 'document "' . ($commentable->titre ?? '#' . $commentable->id) . '"',
            default                                       => 'une ressource',
        };

        // Build a URL if we can
        $url = match (true) {
            $commentable instanceof \App\Models\Courrier => route('filament.admin.resources.courriers.view', $commentable->id),
            $commentable instanceof \App\Models\Document => route('filament.admin.resources.documents.view', $commentable->id),
            default                                      => null,
        };

        return [
            'title'      => 'Vous avez été mentionné',
            'body'       => sprintf('%s vous a mentionné dans un commentaire sur le %s.', $author, $resourceLabel),
            'comment_id' => $this->comment->id,
            'url'        => $url,
        ];
    }
}
