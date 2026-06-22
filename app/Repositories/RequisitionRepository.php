<?php

namespace App\Repositories;

use App\Models\Requisition;
use Illuminate\Pagination\LengthAwarePaginator;

class RequisitionRepository
{
    /**
     * Get paginated requisitions with filters and search.
     */
    public function getPaginatedWithFilters(
        string $search = '',
        string $statusFilter = '',
        string $projectFilter = '',
        string $creatorFilter = '',
        string $vendorFilter = '',
        string $periodFilter = '',
        string $dateFrom = '',
        string $dateTo = '',
        string $sortField = 'id',
        string $sortDirection = 'desc',
        int $perPage = 10
    ): LengthAwarePaginator {
        $query = Requisition::with(['project', 'vendor', 'creator', 'quotations.supplier', 'items.product', 'items.measure', 'items.supplier'])
            ->withCount('items')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('number', 'ilike', "%{$search}%")
                        ->orWhere('annotations', 'ilike', "%{$search}%")
                        ->orWhere('status', 'ilike', "%{$search}%")
                        ->orWhereHas('project', fn($p) => $p->where('name', 'ilike', "%{$search}%"));
                });
            })
            ->when($statusFilter, fn($q) => $q->where('status', $statusFilter))
            ->when($projectFilter, fn($q) => $q->where('project_id', $projectFilter))
            ->when($creatorFilter, fn($q) => $q->where('created_by', $creatorFilter))
            ->when($vendorFilter, function ($q) use ($vendorFilter) {
                $q->where(function ($sub) use ($vendorFilter) {
                    $sub->whereHas('vendor', fn($v) => $v->where('supplier_id', $vendorFilter))
                        ->orWhereHas('items', fn($i) => $i->where('supplier_id', $vendorFilter));
                });
            })
            ->when($periodFilter, function ($q) use ($periodFilter, $dateFrom, $dateTo) {
                if ($periodFilter === 'custom') {
                    if ($dateFrom) {
                        $q->whereDate('date', '>=', $dateFrom);
                    }
                    if ($dateTo) {
                        $q->whereDate('date', '<=', $dateTo);
                    }
                    return;
                }
                match ($periodFilter) {
                    'this_month' => $q->whereMonth('date', now()->month)->whereYear('date', now()->year),
                    'last_month' => $q->whereMonth('date', now()->subMonth()->month)->whereYear('date', now()->subMonth()->year),
                    'this_quarter' => $q->whereBetween('date', [now()->startOfQuarter(), now()->endOfQuarter()]),
                    'this_year' => $q->whereYear('date', now()->year),
                    default => null,
                };
            })
            ->when(true, function ($q) use ($sortField, $sortDirection) {
                $dir = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';
                
                if ($sortField === 'total') {
                    $q->orderBy('cached_total', $dir);
                } elseif ($sortField === 'status') {
                    // Orden jerárquico lógico en lugar de alfabético
                    $q->orderByRaw("CASE status WHEN 'borrador' THEN 1 WHEN 'pendiente' THEN 2 WHEN 'aprobada' THEN 3 WHEN 'rechazada' THEN 4 ELSE 5 END $dir");
                } elseif (in_array($sortField, ['date', 'start_date', 'created_at'])) {
                    // Postgres por defecto pone los NULLS arriba en orden DESC. Esto los manda al final.
                    $q->orderByRaw("\"$sortField\" $dir NULLS LAST");
                } else {
                    $q->orderBy($sortField, $dir);
                }
            });

        return $query->paginate($perPage);
    }
}
