<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Requisition extends Model
{
    protected $fillable = [
        'project_id', 'number', 'annotations', 'status',
        'created_by', 'approved_by', 'rejection_comment',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    protected static function booted()
    {
        static::created(function ($requisition) {
            if (empty($requisition->number)) {
                $projectPrefix = 'PRJ';
                if ($requisition->project) {
                    $projectPrefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $requisition->project->name), 0, 3));
                }
                $requisition->number = sprintf('%s%d-REQ%04d', $projectPrefix, $requisition->project_id, $requisition->id);
                $requisition->saveQuietly();
            }
        });
    }


    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RequisitionItem::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    /** Subtotal estimado (sin IVA). Usa totales del proveedor cuando existen. */
    public function getSubtotalAttribute(): float
    {
        return (float) $this->items->sum(fn ($item) => $item->line_subtotal_computed);
    }

    /** IVA total estimado. tax_amount ya es IVA de línea (total). */
    public function getTaxAmountAttribute(): float
    {
        return (float) $this->items->sum(fn ($item) => (float) ($item->tax_amount ?? 0));
    }

    /** Total estimado de la requisición (subtotal + IVA). Usa totales del proveedor cuando existen. */
    public function getTotalAttribute(): float
    {
        // Si todos los ítems tienen line_total del proveedor, usar la suma directa
        // para evitar acumulación de errores de redondeo
        $allHaveLineTotal = $this->items->every(fn ($item) => $item->line_total !== null);

        if ($allHaveLineTotal) {
            return (float) $this->items->sum(fn ($item) => (float) $item->line_total);
        }

        return round($this->subtotal + $this->tax_amount, 2);
    }
}
