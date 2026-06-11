<?php

namespace App\Models;

use App\Services\DataNormalizerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class Supplier extends Model
{
    use Searchable;

    protected $fillable = [
        'trade_name', 'normalized_name', 'legal_name', 'rfc',
        'category', 'notes',
    ];

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'trade_name' => $this->trade_name,
            'legal_name' => $this->legal_name,
            'rfc' => $this->rfc,
            'category' => $this->category,
            'notes' => $this->notes,
        ];
    }

    protected static function booted()
    {
        static::saving(function ($supplier) {
            if ($supplier->isDirty('trade_name') && ! empty($supplier->trade_name)) {
                $supplier->trade_name = app(DataNormalizerService::class)->normalizeTitleCase($supplier->trade_name);
                $supplier->normalized_name = app(DataNormalizerService::class)->normalizeSupplierName($supplier->trade_name);
            }
        });
    }

    protected $casts = [
        //
    ];

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }
}
