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
        \Illuminate\Support\Facades\Cache::forget('dashboard_global_stats');
        \Illuminate\Support\Facades\Cache::forget('dashboard_global_stats_v3');
        
        if ($requisition->wasChanged('status') || $requisition->status === 'aprobada') {
            \Illuminate\Support\Facades\Cache::forget('dashboard_financial_stats');
            \Illuminate\Support\Facades\Cache::forget('dashboard_monthly_chart');
            \Illuminate\Support\Facades\Cache::forget('dashboard_monthly_chart_v3');
            
            if ($requisition->project) {
                app(ProjectCacheService::class)->recalculateTotalExpenses($requisition->project);
            }

            if ($requisition->wasChanged('project_id') && $requisition->getOriginal('project_id')) {
                $oldProject = \App\Models\Project::find($requisition->getOriginal('project_id'));
                if ($oldProject) {
                    app(ProjectCacheService::class)->recalculateTotalExpenses($oldProject);
                }
            }
        }
    }

    public function deleted(Requisition $requisition): void
    {
        \Illuminate\Support\Facades\Cache::forget('dashboard_global_stats');
        \Illuminate\Support\Facades\Cache::forget('dashboard_global_stats_v3');
        
        if ($requisition->status === 'aprobada') {
            \Illuminate\Support\Facades\Cache::forget('dashboard_financial_stats');
            \Illuminate\Support\Facades\Cache::forget('dashboard_monthly_chart');
            \Illuminate\Support\Facades\Cache::forget('dashboard_monthly_chart_v3');
            
            if ($requisition->project) {
                app(ProjectCacheService::class)->recalculateTotalExpenses($requisition->project);
            }
        }
    }
}
