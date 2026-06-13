<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportCategory extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $category): void {
            if ($category->reports()->exists() || $category->templates()->exists()) {
                throw new \RuntimeException('Suppression impossible: cette categorie est utilisee par des rapports ou des modeles.');
            }
        });
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function templates()
    {
        return $this->hasMany(ReportTemplate::class);
    }
}
