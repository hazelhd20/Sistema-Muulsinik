<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'trade_name', 'legal_name', 'rfc',
        'category', 'contact_info',
    ];

    protected $casts = [
        'contact_info' => 'array',
    ];

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }

    public function productAliases(): HasMany
    {
        return $this->hasMany(ProductAlias::class);
    }
}
