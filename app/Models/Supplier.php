<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'trade_name', 'normalized_name', 'legal_name', 'rfc',
        'category', 'contact_info',
    ];

    protected static function booted()
    {
        static::saving(function ($supplier) {
            if ($supplier->isDirty('trade_name') && !empty($supplier->trade_name)) {
                $supplier->normalized_name = app(\App\Services\DataNormalizerService::class)->normalizeSupplierName($supplier->trade_name);
            }
        });
    }

    protected $casts = [
        'contact_info' => 'array',
    ];

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }

}
