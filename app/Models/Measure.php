<?php

namespace App\Models;

use App\Services\DataNormalizerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Measure extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'abbreviation'];

    protected static function booted()
    {
        static::saving(function ($measure) {
            if ($measure->isDirty('name') && ! empty($measure->name)) {
                $measure->name = app(DataNormalizerService::class)->normalizeTitleCase($measure->name);
            }
        });
    }

    /**
     * Retorna la lista de unidades de medida ordenadas por nombre.
     */
    public static function getOptions()
    {
        return static::orderBy('name')->get();
    }

    /**
     * Retorna la lista de unidades de medida en formato array (id => name).
     */
    public static function getOptionsArray(): array
    {
        return static::orderBy('name')->pluck('name', 'id')->toArray();
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function requisitionItems(): HasMany
    {
        return $this->hasMany(RequisitionItem::class);
    }
}
