<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = ['canonical_name', 'normalized_name', 'measure_id', 'description', 'category_id'];

    protected static function booted()
    {
        static::saving(function ($product) {
            if ($product->isDirty('canonical_name') && !empty($product->canonical_name)) {
                $normalizer = app(\App\Services\DataNormalizerService::class);
                // normalizeProductName() aplica: MAYÚSCULAS + limpieza de códigos SKU + protección de medidas
                $product->canonical_name = $normalizer->normalizeProductName($product->canonical_name);
                $product->normalized_name = $normalizer->normalizeText($product->canonical_name);
            }
        });
    }

    public function measure(): BelongsTo
    {
        return $this->belongsTo(Measure::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
