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
 * @property int|null $client_id
 * @property-read \App\Models\Client|null $client
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
        'name', 'description', 'client_id', 'budget',
        'start_date', 'end_date', 'status',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => \App\Enums\ProjectStatus::class,
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
            'client_id' => $this->client_id,
            'status' => $this->status,
        ];
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function client(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Client::class);
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

        $requisitions = (float) $this->requisitions()
            ->where('status', 'aprobada')
            ->where('created_at', '>=', $dateFrom)
            ->sum('cached_total');

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
