<?php

namespace App\Models;

use App\Services\DataNormalizerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vendor extends Model
{
    protected $fillable = ['supplier_id', 'name', 'phone', 'email'];

    protected static function booted()
    {
        static::saving(function ($vendor) {
            if ($vendor->isDirty('name') && ! empty($vendor->name)) {
                $vendor->name = app(DataNormalizerService::class)->normalizeTitleCase($vendor->name);
            }
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
