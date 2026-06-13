<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowTemplateStep extends Model
{
    protected $guarded = [];

    protected $casts = [
        'sla_hours' => 'integer',
    ];

    // ── Label for action ──────────────────────────────────────────────────────

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

    public function template()
    {
        return $this->belongsTo(WorkflowTemplate::class, 'workflow_template_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    public function escalationUser()
    {
        return $this->belongsTo(User::class, 'escalation_user_id');
    }
}
