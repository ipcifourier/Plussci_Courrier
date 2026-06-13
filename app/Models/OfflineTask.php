<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfflineTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_uuid',
        'label',
        'done',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'done' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
