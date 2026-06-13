<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentWorkflowStep extends Model
{
    protected $guarded = [];

    protected $casts = [
        'sla_hours' => 'integer',
        'due_at' => 'datetime',
        'decided_at' => 'datetime',
        'escalated_at' => 'datetime',
    ];

    public function slaSourceLabel(): string
    {
        return match ($this->sla_source) {
            'template_custom' => 'Template (custom)',
            'global_priority' => 'Règle globale: priorité',
            'global_confidentiality' => 'Règle globale: confidentialité',
            'global_type' => 'Règle globale: type document',
            'global_default' => 'Règle globale: défaut',
            'template_default' => 'Template (par défaut)',
            default => 'Non défini',
        };
    }

    // ── Status helpers ────────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function deadlineAt()
    {
        if ($this->due_at) {
            return $this->due_at;
        }

        if (! $this->created_at) {
            return null;
        }

        return $this->created_at->copy()->addHours(max(1, (int) ($this->sla_hours ?? 24)));
    }

    public function isOverdue(): bool
    {
        $deadline = $this->deadlineAt();

        return $this->isPending() && $deadline && $deadline->isPast();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending'  => 'En attente',
            'approved' => 'Approuvé',
            'rejected' => 'Rejeté',
            'skipped'  => 'Ignoré',
            default    => ucfirst($this->status),
        };
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'review'   => 'Revue',
            'approve'  => 'Approbation',
            'validate' => 'Validation',
            default    => ucfirst($this->action),
        };
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function workflow()
    {
        return $this->belongsTo(DocumentWorkflow::class, 'document_workflow_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function escalationUser()
    {
        return $this->belongsTo(User::class, 'escalation_user_id');
    }
}
