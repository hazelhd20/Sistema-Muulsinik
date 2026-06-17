<?php

namespace App\Models;

use App\Services\DataNormalizerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = ['name'];

    protected static function booted()
    {
        static::saving(function ($category) {
            if ($category->isDirty('name') && ! empty($category->name)) {
                $category->name = app(DataNormalizerService::class)->normalizeTitleCase($category->name);
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
