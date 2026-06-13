<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourrierApproval extends Model
{
    protected $guarded = [];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function courrier()
    {
        return $this->belongsTo(Courrier::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
