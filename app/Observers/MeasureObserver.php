<?php

namespace App\Observers;

use App\Models\Measure;
use Illuminate\Support\Facades\Cache;

class MeasureObserver
{
    /**
     * Handle the Measure "saved" event.
     */
    public function saved(Measure $measure): void
    {
        Cache::forget('catalog_measures');
    }

    /**
     * Handle the Measure "deleted" event.
     */
    public function deleted(Measure $measure): void
    {
        Cache::forget('catalog_measures');
    }
}
