<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentAccessRule extends Model
{
    protected $guarded = [];

    protected $casts = [
        'can_view'     => 'boolean',
        'can_download' => 'boolean',
        'can_edit'     => 'boolean',
        'can_share'    => 'boolean',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function role()
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if a given User satisfies this rule.
     */
    public function matchesUser(User $user): bool
    {
        if ($this->user_id !== null) {
            return $this->user_id === $user->id;
        }

        if ($this->role_id !== null) {
            return $user->roles()->where('id', $this->role_id)->exists();
        }

        return false;
    }
}
