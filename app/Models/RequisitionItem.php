<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisitionItem extends Model
{
    protected $fillable = [
        'requisition_id', 'product_id', 'product_name',
        'quantity', 'unit', 'unit_price', 'unit_price_original',
        'tax_amount', 'tax_source',
        'line_subtotal', 'line_total',
        'supplier_id',
    ];

    protected $casts = [
        'quantity'             => 'decimal:4',
        'unit_price'           => 'decimal:2',
        'unit_price_original'  => 'decimal:2',
        'tax_amount'           => 'decimal:2',
        'line_subtotal'        => 'decimal:2',
        'line_total'           => 'decimal:2',
    ];

    /**
     * Precio unitario con IVA incluido.
     * Si hay line_total del proveedor, lo deduce de ahí para máxima precisión.
     * Si no, calcula a partir de tax_amount (IVA de línea) / quantity.
     */
    public function getUnitPriceWithTaxAttribute(): float
    {
        if ($this->line_total !== null && $this->quantity > 0) {
            return round((float) $this->line_total / (float) $this->quantity, 2);
        }

        $taxPerUnit = ($this->tax_amount !== null && $this->quantity > 0)
            ? (float) $this->tax_amount / (float) $this->quantity
            : 0;

        return round((float) $this->unit_price + $taxPerUnit, 2);
    }

    /**
     * Subtotal de línea sin IVA.
     *
     * Prioridad: valor del proveedor (almacenado) → recálculo.
     * El valor del proveedor es la fuente de verdad fiscal;
     * el recálculo solo aplica si no se proporcionó.
     */
    public function getLineSubtotalComputedAttribute(): float
    {
        if ($this->line_subtotal !== null) {
            return (float) $this->line_subtotal;
        }

        return round((float) $this->unit_price * (float) $this->quantity, 2);
    }

    /**
     * Total de línea con IVA.
     *
     * Prioridad: valor del proveedor (almacenado) → recálculo.
     */
    public function getLineTotalComputedAttribute(): float
    {
        if ($this->line_total !== null) {
            return (float) $this->line_total;
        }

        $subtotal = $this->line_subtotal_computed;
        $tax = (float) ($this->tax_amount ?? 0);

        return round($subtotal + $tax, 2);
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
