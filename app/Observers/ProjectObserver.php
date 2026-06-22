<?php

namespace App\Observers;

use App\Models\Project;
use Illuminate\Support\Facades\Cache;

class ProjectObserver
{
    /**
     * Handle the Project "saved" event.
     */
    public function saved(Project $project): void
    {
        Cache::forget('dashboard_global_stats');
        Cache::forget('projects.activos.array');
    }

    /**
     * Handle the Project "deleted" event.
     */
    public function deleted(Project $project): void
    {
        Cache::forget('dashboard_global_stats');
        Cache::forget('projects.activos.array');
    }
}
