<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = ['canonical_name', 'unit', 'description', 'category'];

    public function aliases(): HasMany
    {
        return $this->hasMany(ProductAlias::class);
    }
}
