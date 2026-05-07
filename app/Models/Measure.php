<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Measure extends Model
{
    protected $fillable = ['name', 'abbreviation'];

    protected static function booted()
    {
        static::saving(function ($measure) {
            if ($measure->isDirty('name') && !empty($measure->name)) {
                $measure->name = app(\App\Services\DataNormalizerService::class)->normalizeTitleCase($measure->name);
            }
        });
    }
}
