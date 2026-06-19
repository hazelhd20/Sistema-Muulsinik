<?php

namespace App\Observers;

use App\Models\Requisition;
use App\Services\ProjectCacheService;

class RequisitionObserver
{
    public function created(Requisition $requisition): void
    {
        if (empty($requisition->number)) {
            $projectPrefix = 'PRJ';
            if ($requisition->project) {
                $projectPrefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $requisition->project->name), 0, 3));
            }
            $requisition->number = sprintf('%s%d-REQ%04d', $projectPrefix, $requisition->project_id, $requisition->id);
            $requisition->saveQuietly();
        }
    }

    public function saved(Requisition $requisition): void
    {
        if ($requisition->wasChanged('status') || $requisition->status === 'aprobada') {
            if ($requisition->project) {
                app(ProjectCacheService::class)->recalculateTotalExpenses($requisition->project);
            }
        }
    }

    public function deleted(Requisition $requisition): void
    {
        if ($requisition->status === 'aprobada') {
            if ($requisition->project) {
                app(ProjectCacheService::class)->recalculateTotalExpenses($requisition->project);
            }
        }
    }
}
