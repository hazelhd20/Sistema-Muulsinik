<?php

namespace App\Models;

use App\Services\DataNormalizerService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Product extends Model
{
    
    use HasFactory, Searchable, SoftDeletes;

    protected $fillable = ['canonical_name', 'normalized_name', 'measure_id', 'description', 'category_id'];

    protected static function booted()
    {
        static::saving(function ($product) {
            if ($product->isDirty('canonical_name') && ! empty($product->canonical_name)) {
                $normalizer = app(DataNormalizerService::class);
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

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'canonical_name' => $this->canonical_name,
            'normalized_name' => $this->normalized_name,
            'category_name' => $this->category ? $this->category->name : '',
            'description' => $this->description,
        ];
    }
}
