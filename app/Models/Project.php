<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'name', 'description', 'client', 'budget',
        'start_date', 'end_date', 'status',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

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

    /** Gasto total acumulado del proyecto (Directo + Distribuido + Requisiciones Aprobadas). */
    public function getTotalExpensesAttribute(): float
    {
        $direct = (float) $this->expenses()->sum('amount');
        $distributed = (float) $this->expenseAllocations()->sum('amount');
        
        $requisitions = (float) $this->requisitions()
            ->where('status', 'aprobada')
            ->with('items')
            ->get()
            ->sum(fn($req) => $req->total);

        return $direct + $distributed + $requisitions;
    }

    /** Gasto total del proyecto en un período específico (Directo + Distribuido + Requisiciones Aprobadas). */
    public function getSpentInPeriod(\Carbon\Carbon $dateFrom): float
    {
        $direct = (float) $this->expenses()
            ->where('date', '>=', $dateFrom)
            ->sum('amount');

        $distributed = (float) $this->expenseAllocations()
            ->whereHas('expense', fn($q) => $q->where('date', '>=', $dateFrom))
            ->sum('amount');

        $requisitions = (float) $this->requisitions()
            ->where('status', 'aprobada')
            ->where('created_at', '>=', $dateFrom)
            ->with('items')
            ->get()
            ->sum(fn($req) => $req->total);

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
