<?php

namespace App\Observers;

use App\Models\Supplier;
use Illuminate\Support\Facades\Cache;

class SupplierObserver
{
    /**
     * Handle the Supplier "saved" event.
     */
    public function saved(Supplier $supplier): void
    {
        Cache::forget('dashboard_global_stats');
        Cache::forget('catalog_suppliers');
        Cache::forget('suppliers.all.array');
    }

    /**
     * Handle the Supplier "deleted" event.
     */
    public function deleted(Supplier $supplier): void
    {
        Cache::forget('dashboard_global_stats');
        Cache::forget('catalog_suppliers');
        Cache::forget('suppliers.all.array');
    }
}
