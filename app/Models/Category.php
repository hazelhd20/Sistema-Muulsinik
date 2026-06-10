<?php

namespace App\Models;

use App\Services\DataNormalizerService;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name'];

    protected static function booted()
    {
        static::saving(function ($category) {
            if ($category->isDirty('name') && ! empty($category->name)) {
                $category->name = app(DataNormalizerService::class)->normalizeTitleCase($category->name);
            }
        });
    }
}
