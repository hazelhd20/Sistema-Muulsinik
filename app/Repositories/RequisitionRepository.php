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
        return Requisition::search($search)
            ->query(function ($query) use (
                $statusFilter, $projectFilter, $creatorFilter, $vendorFilter, $periodFilter, $dateFrom, $dateTo, $sortField, $sortDirection
            ) {
                $query->with(['project', 'vendor', 'creator', 'quotations', 'items.product', 'items.measure', 'items.supplier'])
                    ->withCount('items')
                    ->when($statusFilter, fn($q) => $q->where('status', $statusFilter))
                    ->when($projectFilter, fn($q) => $q->where('project_id', $projectFilter))
                    ->when($creatorFilter, fn($q) => $q->where('created_by', $creatorFilter))
                    ->when($vendorFilter, fn($q) => $q->where('vendor_id', $vendorFilter))
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
                    ->orderBy($sortField, $sortDirection);
            })
            ->paginate($perPage);
    }
}
