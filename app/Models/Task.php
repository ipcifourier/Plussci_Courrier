<?php

namespace App\Models;

use App\Notifications\TaskAssignedNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Task extends Model
{
    protected $guarded = [];

    protected $casts = [
        'due_date'          => 'date',
        'alerte_envoyee_at' => 'datetime',
    ];

    /** True si l'échéance approche dans les prochaines $hours heures et n'est pas terminée. */
    public function isApproachingDeadline(int $hours = 24): bool
    {
        return $this->due_date !== null
            && ! in_array($this->status, ['done', 'cancelled'])
            && $this->due_date->greaterThanOrEqualTo(today())
            && $this->due_date->lessThanOrEqualTo(now()->addHours($hours));
    }

    /** True si une alerte a déjà été envoyée aujourd'hui. */
    public function alerteEnvoyeeAujourdhui(): bool
    {
        return $this->alerte_envoyee_at !== null
            && $this->alerte_envoyee_at->isToday();
    }

    protected static function booted(): void
    {
        static::created(function (self $task): void {
            if ($task->assignee_id && $task->assignee_id !== $task->assigner_id) {
                optional($task->assignee)->notify(new TaskAssignedNotification($task));
            }
        });

        static::updating(function (self $task): void {
            if ($task->isDirty('status') && $task->getOriginal('status') !== $task->status) {
                $userId = Auth::id() ?? $task->assigner_id;

                TaskHistory::create([
                    'task_id'     => $task->id,
                    'changed_by'  => $userId,
                    'from_status' => $task->getOriginal('status'),
                    'to_status'   => $task->status,
                ]);
            }
        });
    }

    public function taskable()
    {
        return $this->morphTo();
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigner_id');
    }

    public function histories()
    {
        return $this->hasMany(TaskHistory::class);
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->due_date->isPast()
            && ! in_array($this->status, ['done', 'cancelled']);
    }
}
