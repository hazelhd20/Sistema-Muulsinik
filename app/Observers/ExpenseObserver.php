<?php

namespace App\Observers;

use App\Models\Expense;
use App\Services\ProjectCacheService;

class ExpenseObserver
{
    public function saved(Expense $expense): void
    {
        \Illuminate\Support\Facades\Cache::forget('dashboard_financial_stats');
        \Illuminate\Support\Facades\Cache::forget('dashboard_monthly_chart');
        
        if ($expense->project) {
            app(ProjectCacheService::class)->recalculateTotalExpenses($expense->project);
        }
    }

    public function deleted(Expense $expense): void
    {
        \Illuminate\Support\Facades\Cache::forget('dashboard_financial_stats');
        \Illuminate\Support\Facades\Cache::forget('dashboard_monthly_chart');

        if ($expense->project) {
            app(ProjectCacheService::class)->recalculateTotalExpenses($expense->project);
        }
    }
}
