<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Correspondant extends Model
{
protected $guarded = [];

    public function courriers()
    {
        return $this->hasMany(Courrier::class);
    }
}
