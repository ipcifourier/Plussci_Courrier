<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentWorkflow extends Model
{
    protected $guarded = [];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    // ── Status helpers ────────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, ['approved', 'rejected', 'cancelled']);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending'   => 'En cours',
            'approved'  => 'Approuvé',
            'rejected'  => 'Rejeté',
            'cancelled' => 'Annulé',
            default     => ucfirst($this->status),
        };
    }

    /** Returns the current pending step, or null if no step is pending. */
    public function currentStep(): ?DocumentWorkflowStep
    {
        return $this->steps()
            ->where('step_order', $this->current_step_order)
            ->where('status', 'pending')
            ->first();
    }

    /** Progress: number of approved steps / total steps. */
    public function progressPercent(): int
    {
        $total = $this->steps()->count();

        if ($total === 0) {
            return 100;
        }

        $done = $this->steps()->where('status', 'approved')->count();

        return (int) round($done / $total * 100);
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function template()
    {
        return $this->belongsTo(WorkflowTemplate::class, 'workflow_template_id');
    }

    public function initiatedBy()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function steps()
    {
        return $this->hasMany(DocumentWorkflowStep::class)->orderBy('step_order');
    }
}
