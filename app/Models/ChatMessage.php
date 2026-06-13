<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ChatMessage extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'sender_id',
        'recipient_id',
        'body',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }

    public function shouldDisplayBody(): bool
    {
        return isset($this->body) && trim((string) $this->body) !== '';
    }

    public function isPreviewableAttachment(\Spatie\MediaLibrary\MediaCollections\Models\Media $attachment): bool
    {
        return str_starts_with((string) ($attachment->mime_type ?? ''), 'image/');
    }
}
