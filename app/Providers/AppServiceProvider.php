<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Models\Requisition::observe(\App\Observers\RequisitionObserver::class);
        \App\Models\Expense::observe(\App\Observers\ExpenseObserver::class);
        \App\Models\ExpenseAllocation::observe(\App\Observers\ExpenseAllocationObserver::class);
        \App\Models\Project::observe(\App\Observers\ProjectObserver::class);
        \App\Models\Supplier::observe(\App\Observers\SupplierObserver::class);
        \App\Models\User::observe(\App\Observers\UserObserver::class);
        \App\Models\RequisitionItem::observe(\App\Observers\RequisitionItemObserver::class);
    }
}
