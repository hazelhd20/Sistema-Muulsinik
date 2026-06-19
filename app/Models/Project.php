<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $client
 * @property float $budget
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property string $status
 * @property-read float $total_expenses
 * @property-read float $budget_used_percent
 */
class Project extends Model
{
    use Searchable, SoftDeletes;

    protected $fillable = [
        'name', 'description', 'client', 'budget',
        'start_date', 'end_date', 'status',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'client' => $this->client,
            'status' => $this->status,
        ];
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function requisitions(): HasMany
    {
        return $this->hasMany(Requisition::class);
    }

    public function expenseAllocations(): HasMany
    {
        return $this->hasMany(ExpenseAllocation::class);
    }

    /** Gasto total acumulado del proyecto. Obtenido desde la columna de caché materializada. */
    public function getTotalExpensesAttribute(): float
    {
        return (float) $this->total_expenses_cache;
    }

    /**
     * @deprecated Use ProjectCacheService::recalculateTotalExpenses() instead.
     */
    public function recalculateTotalExpensesCache(): void
    {
        app(\App\Services\ProjectCacheService::class)->recalculateTotalExpenses($this);
    }

    /** Gasto total del proyecto en un período específico (Directo + Distribuido + Requisiciones Aprobadas). */
    public function getSpentInPeriod(Carbon $dateFrom): float
    {
        $direct = (float) $this->expenses()
            ->where('date', '>=', $dateFrom)
            ->sum('amount');

        $distributed = (float) $this->expenseAllocations()
            ->whereHas('expense', fn ($q) => $q->where('date', '>=', $dateFrom))
            ->sum('amount');

        $requisitions = (float) RequisitionItem::join('requisitions', 'requisitions.id', '=', 'requisition_items.requisition_id')
            ->where('requisitions.project_id', $this->id)
            ->where('requisitions.status', 'aprobada')
            ->where('requisitions.created_at', '>=', $dateFrom)
            ->sum(DB::raw('COALESCE(requisition_items.line_total, (requisition_items.unit_price * requisition_items.quantity) + COALESCE(requisition_items.tax_amount, 0))'));

        return $direct + $distributed + $requisitions;
    }

    /** Porcentaje del presupuesto consumido. */
    public function getBudgetUsedPercentAttribute(): float
    {
        if ($this->budget <= 0) {
            return 0;
        }

        return round(($this->total_expenses / $this->budget) * 100, 1);
    }
}
