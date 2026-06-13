<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WorkflowTemplate extends Model
{
    protected $guarded = [];

    protected $casts = [
        'trigger_types'                  => 'array',
        'trigger_confidentiality_levels' => 'array',
        'is_active'                      => 'boolean',
        'auto_start'                     => 'boolean',
    ];

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function triggersList(): string
    {
        return empty($this->trigger_types)
            ? 'Tous types'
            : implode(', ', $this->trigger_types);
    }

    public function confidentialityTriggersList(): string
    {
        return empty($this->trigger_confidentiality_levels)
            ? 'Tous niveaux'
            : implode(', ', $this->trigger_confidentiality_levels);
    }

    public function supportsDocument(Document $document): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if (! empty($this->trigger_types)) {
            $documentType = Str::lower((string) $document->type_document);
            $allowedTypes = collect($this->trigger_types)
                ->filter(fn (mixed $value): bool => filled($value))
                ->map(fn (mixed $value): string => Str::lower((string) $value))
                ->all();

            if (! in_array($documentType, $allowedTypes, true)) {
                return false;
            }
        }

        if (! empty($this->trigger_confidentiality_levels)) {
            $level = (string) $document->confidentiality_level;

            if (! in_array($level, $this->trigger_confidentiality_levels, true)) {
                return false;
            }
        }

        return true;
    }

    public function stepCount(): int
    {
        return $this->steps()->count();
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function steps()
    {
        return $this->hasMany(WorkflowTemplateStep::class)->orderBy('step_order');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documentWorkflows()
    {
        return $this->hasMany(DocumentWorkflow::class, 'workflow_template_id');
    }
}
