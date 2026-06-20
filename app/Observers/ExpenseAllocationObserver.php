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

        if ($allocation->wasChanged('project_id') && $allocation->getOriginal('project_id')) {
            $oldProject = \App\Models\Project::find($allocation->getOriginal('project_id'));
            if ($oldProject) {
                app(ProjectCacheService::class)->recalculateTotalExpenses($oldProject);
            }
        }
    }

    public function deleted(ExpenseAllocation $allocation): void
    {
        if ($allocation->project) {
            app(ProjectCacheService::class)->recalculateTotalExpenses($allocation->project);
        }
    }
}
