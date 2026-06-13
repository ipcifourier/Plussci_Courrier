<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportRecommendation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'due_date' => 'date',
        'implemented_at' => 'datetime',
        'progress_percent' => 'integer',
    ];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->due_date->isPast()
            && ! in_array($this->implementation_status, ['implemented', 'cancelled'], true);
    }
}
