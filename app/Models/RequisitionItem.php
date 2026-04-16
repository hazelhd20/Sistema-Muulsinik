<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisitionItem extends Model
{
    protected $fillable = [
        'requisition_id', 'product_id', 'product_name',
        'quantity', 'unit', 'unit_price', 'supplier_id',
        'homologation_status',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
    ];

    /** Indica si el producto ya fue homologado con el catálogo maestro. */
    public function isHomologated(): bool
    {
        return $this->homologation_status === 'homologated';
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
