<?php

namespace App\Observers;

use App\Models\Expense;
use App\Services\ProjectCacheService;

class ExpenseObserver
{
    public function saved(Expense $expense): void
    {
        if ($expense->project) {
            app(ProjectCacheService::class)->recalculateTotalExpenses($expense->project);
        }
    }

    public function deleted(Expense $expense): void
    {
        if ($expense->project) {
            app(ProjectCacheService::class)->recalculateTotalExpenses($expense->project);
        }
    }
}
