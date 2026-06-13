<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileManagerOfflineTask extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'payload',
        'status',
        'executed_at',
        'error_message',
    ];

    protected $casts = [
        'payload'     => 'array',
        'executed_at' => 'datetime',
    ];

    /** Statuts possibles */
    public const STATUS_PENDING  = 'pending';
    public const STATUS_DONE     = 'done';
    public const STATUS_FAILED   = 'failed';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
