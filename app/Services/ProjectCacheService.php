<?php

namespace App\Services;

use App\Models\Project;
use App\Models\RequisitionItem;
use Illuminate\Support\Facades\DB;

class ProjectCacheService
{
    /**
     * Recalcula el gasto total directamente desde la base de datos y actualiza la caché del proyecto.
     * Combina gastos directos, gastos distribuidos y requisiciones aprobadas.
     *
     * @param Project $project
     * @return void
     */
    public function recalculateTotalExpenses(Project $project): void
    {
        $direct = (float) $project->expenses()->sum('amount');
        $distributed = (float) $project->expenseAllocations()->sum('amount');

        $requisitions = (float) $project->requisitions()
            ->where('status', 'aprobada')
            ->sum('cached_total');

        $project->forceFill(['total_expenses_cache' => $direct + $distributed + $requisitions])->saveQuietly();
    }
}
