<?php

namespace App\Models;

use App\Notifications\CommentPostedNotification;
use App\Notifications\MentionNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Comment extends Model
{
    public const KIND_COMMENT = 'comment';

    public const KIND_ANNOTATION = 'annotation';

    protected $guarded = [];

    protected $casts = [
        'is_internal' => 'boolean',
        'annotation_data' => 'array',
        'resolved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // After a comment is saved, create Mention records and notify @mentioned users
        static::created(function (self $comment): void {
            $mentionedUserIds = $comment->parseMentionedUsers()->map(function (User $user) use ($comment): int {
                // Avoid duplicate mentions
                $mention = $comment->mentions()->firstOrCreate(
                    ['mentioned_user_id' => $user->id],
                    ['notified_at' => now()]
                );

                if (! $mention->wasRecentlyCreated) {
                    return $user->id;
                }

                $user->notify(new MentionNotification($comment));
                return $user->id;
            });

            $comment->notifyStakeholders($mentionedUserIds);
        });
    }

    public function commentable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mentions()
    {
        return $this->hasMany(Mention::class);
    }

    public function isAnnotation(): bool
    {
        return $this->kind === self::KIND_ANNOTATION;
    }

    /**
     * Parse @mentions from body and return matched User models.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function parseMentionedUsers(): \Illuminate\Support\Collection
    {
        preg_match_all('/@([a-zA-ZÀ-ÿ0-9._-]+)/', $this->body, $matches);

        if (empty($matches[1])) {
            return collect();
        }

        return User::whereIn('name', $matches[1])->get();
    }

    /**
     * Notify key participants when a new comment/annotation is posted.
     *
     * @param  \Illuminate\Support\Collection<int, int>  $mentionedUserIds
     */
    public function notifyStakeholders(Collection $mentionedUserIds): void
    {
        $commentable = $this->commentable;

        if (! $commentable) {
            return;
        }

        $recipientIds = collect();

        if ($commentable instanceof Courrier && $commentable->user_id) {
            $recipientIds->push((int) $commentable->user_id);
        }

        if ($commentable instanceof Document && $commentable->auteur_id) {
            $recipientIds->push((int) $commentable->auteur_id);
        }

        $participantIds = $commentable->comments()
            ->where('id', '!=', $this->id)
            ->pluck('user_id');

        $recipientIds = $recipientIds
            ->merge($participantIds)
            ->filter()
            ->unique()
            ->reject(fn (int $userId): bool => $userId === (int) $this->user_id)
            ->reject(fn (int $userId): bool => $mentionedUserIds->contains($userId));

        if ($recipientIds->isEmpty()) {
            return;
        }

        User::query()
            ->whereIn('id', $recipientIds->values())
            ->each(fn (User $user): mixed => $user->notify(new CommentPostedNotification($this)));
    }
}
