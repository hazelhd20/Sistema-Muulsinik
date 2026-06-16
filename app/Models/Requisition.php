<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property int $project_id
 * @property int|null $vendor_id
 * @property string|null $number
 * @property string|null $annotations
 * @property string $status
 * @property int $created_by
 * @property int|null $approved_by
 * @property string|null $rejection_comment
 * @property Carbon|null $date
 * @property-read float $subtotal
 * @property-read float $tax_amount
 * @property-read float $total
 */
class Requisition extends Model
{
    use BroadcastsEvents, Searchable, HasFactory, SoftDeletes;

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'project_name' => $this->project ? $this->project->name : '',
            'annotations' => $this->annotations,
            'status' => $this->status,
        ];
    }

    public function broadcastOn(string $event): array
    {
        return [
            new PrivateChannel('requisitions.index'),
            new PrivateChannel('App.Models.Requisition.' . $this->id),
        ];
    }

    public function broadcastWhen(string $event): bool
    {
        // No transmitir actualizaciones por WebSockets si es solo un borrador
        return $this->status !== 'borrador';
    }
    protected $fillable = [
        'project_id',
        'vendor_id',
        'number',
        'annotations',
        'status',
        'created_by',
        'approved_by',
        'rejection_comment',
        'date',
        'cached_subtotal',
        'cached_total',
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

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
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

    public function activities(): HasMany
    {
        return $this->hasMany(RequisitionActivity::class)->latest();
    }

    /** Subtotal estimado (sin IVA). Usa totales materializados si existen. */
    public function getSubtotalAttribute(): float
    {
        if ($this->cached_subtotal > 0) {
            return (float) $this->cached_subtotal;
        }
        return (float) $this->items->sum(fn ($item) => $item->line_subtotal_computed);
    }

    /** IVA total estimado. tax_amount ya es IVA de línea (total). */
    public function getTaxAmountAttribute(): float
    {
        return (float) $this->items->sum(fn ($item) => (float) ($item->tax_amount ?? 0));
    }

    /** Total estimado de la requisición. Usa totales materializados si existen. */
    public function getTotalAttribute(): float
    {
        if ($this->cached_total > 0) {
            return (float) $this->cached_total;
        }

        $allHaveLineTotal = $this->items->every(fn ($item) => $item->line_total !== null);

        if ($allHaveLineTotal && $this->items->isNotEmpty()) {
            return (float) $this->items->sum(fn ($item) => (float) $item->line_total);
        }

        return round($this->subtotal + $this->tax_amount, 2);
    }

    /**
     * Recalcula y materializa los totales en la base de datos.
     */
    public function recalculateTotals(): void
    {
        $subtotal = (float) $this->items->sum(fn ($item) => $item->line_subtotal_computed);
        
        $allHaveLineTotal = $this->items->every(fn ($item) => $item->line_total !== null);
        $total = $allHaveLineTotal && $this->items->isNotEmpty()
            ? (float) $this->items->sum(fn ($item) => (float) $item->line_total)
            : round($subtotal + $this->tax_amount, 2);

        $this->updateQuietly([
            'cached_subtotal' => $subtotal,
            'cached_total' => $total,
        ]);
    }
}
