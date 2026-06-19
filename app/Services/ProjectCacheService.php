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

        $requisitions = (float) RequisitionItem::join('requisitions', 'requisitions.id', '=', 'requisition_items.requisition_id')
            ->where('requisitions.project_id', $project->id)
            ->where('requisitions.status', 'aprobada')
            ->sum(DB::raw('COALESCE(requisition_items.line_total, (requisition_items.unit_price * requisition_items.quantity) + COALESCE(requisition_items.tax_amount, 0))'));

        $project->forceFill(['total_expenses_cache' => $direct + $distributed + $requisitions])->saveQuietly();
    }
}
