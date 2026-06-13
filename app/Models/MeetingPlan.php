<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingPlan extends Model
{
    protected $fillable = [
        'planning_year',
        'committee_type',
        'gtt_id',
        'target_count',
        'comment',
    ];

    protected $casts = [
        'planning_year' => 'integer',
        'target_count'  => 'integer',
        'gtt_id'        => 'integer',
    ];

    public function gtt(): BelongsTo
    {
        return $this->belongsTo(Gtt::class);
    }
}
