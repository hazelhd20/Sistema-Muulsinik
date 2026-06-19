<?php

namespace App\Observers;

use App\Models\ExpenseAllocation;
use App\Services\ProjectCacheService;

class ExpenseAllocationObserver
{
    public function saved(ExpenseAllocation $allocation): void
    {
        if ($allocation->project) {
            app(ProjectCacheService::class)->recalculateTotalExpenses($allocation->project);
        }
    }

    public function deleted(ExpenseAllocation $allocation): void
    {
        if ($allocation->project) {
            app(ProjectCacheService::class)->recalculateTotalExpenses($allocation->project);
        }
    }
}
