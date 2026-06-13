<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportApproval extends Model
{
    protected $guarded = [];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
