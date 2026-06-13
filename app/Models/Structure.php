<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Structure extends Model
{
    protected $fillable = ['nom', 'type', 'adresse', 'email', 'telephone'];

    public function bureauMembers(): HasMany
    {
        return $this->hasMany(BureauMember::class);
    }
}
