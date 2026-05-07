<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name'];

    protected static function booted()
    {
        static::saving(function ($category) {
            if ($category->isDirty('name') && !empty($category->name)) {
                $category->name = app(\App\Services\DataNormalizerService::class)->normalizeTitleCase($category->name);
            }
        });
    }
}
