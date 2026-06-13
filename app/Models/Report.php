<?php

namespace App\Models;

use App\Notifications\ReportApprovalDecisionNotification;
use App\Notifications\ReportApprovalRequestedNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Report extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = [];

    protected $casts = [
        'date_start' => 'date',
        'date_end' => 'date',
        'participants_json' => 'array',
        'metadata_json' => 'array',
        'requires_approval' => 'boolean',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $report): void {
            if (! $report->reference) {
                $report->reference = 'RPT-' . now()->format('ymd') . '-' . strtoupper(Str::random(4));
            }
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('reports')->useDisk('public');
    }

    public function category()
    {
        return $this->belongsTo(ReportCategory::class, 'report_category_id');
    }

    public function template()
    {
        return $this->belongsTo(ReportTemplate::class, 'report_template_id');
    }

    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function missionCourrier()
    {
        return $this->belongsTo(Courrier::class, 'mission_courrier_id');
    }

    public function tdrDocument()
    {
        return $this->belongsTo(Document::class, 'tdr_document_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recommendations()
    {
        return $this->hasMany(ReportRecommendation::class);
    }

    public function approvals()
    {
        return $this->hasMany(ReportApproval::class);
    }

    public function notifyCurrentApprovers(): void
    {
        if (! $this->current_approval_level) {
            return;
        }

        $this->loadMissing('approvals.approver');

        $this->approvals
            ->where('level', $this->current_approval_level)
            ->where('status', 'pending')
            ->each(fn (ReportApproval $approval) => $approval->approver?->notify(new ReportApprovalRequestedNotification($this)));
    }

    public function notifyInitiatorDecision(string $decision, ?string $comment = null): void
    {
        $this->loadMissing('creator');

        $this->creator?->notify(new ReportApprovalDecisionNotification($this, $decision, $comment));
    }
}
