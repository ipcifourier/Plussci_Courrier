<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingTask extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $task): void {
            if ($task->status === 'done' && blank($task->completed_at)) {
                $task->completed_at = now();
            }

            if ($task->status !== 'done') {
                $task->completed_at = null;
            }
        });
    }

    protected $fillable = [
        'meeting_id',
        'agenda_item_id',
        'title',
        'description',
        'assigned_to',
        'due_at',
        'status',
        'priority',
        'completed_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function agendaItem(): BelongsTo
    {
        return $this->belongsTo(MeetingAgendaItem::class, 'agenda_item_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
