<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisitionItem extends Model
{
    protected $fillable = [
        'requisition_id', 'product_id', 'product_name',
        'quantity', 'unit', 'unit_price', 'unit_price_original',
        'tax_amount', 'tax_source', 'supplier_id',
    ];

    protected $casts = [
        'quantity'             => 'decimal:4',
        'unit_price'           => 'decimal:2',
        'unit_price_original'  => 'decimal:2',
        'tax_amount'           => 'decimal:2',
    ];

    /**
     * Precio unitario con IVA incluido.
     * Si no hay IVA registrado, devuelve el precio base.
     */
    public function getUnitPriceWithTaxAttribute(): float
    {
        return round((float) $this->unit_price + (float) ($this->tax_amount ?? 0), 2);
    }

    /** Total de línea sin IVA. */
    public function getLineTotalAttribute(): float
    {
        return round((float) $this->unit_price * (float) $this->quantity, 2);
    }

    /** Total de línea con IVA. */
    public function getLineTotalWithTaxAttribute(): float
    {
        return round($this->unit_price_with_tax * (float) $this->quantity, 2);
    }

    /** Indica si el IVA fue resuelto (tiene fuente definida). */
    public function isTaxResolved(): bool
    {
        return $this->tax_source !== null;
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
