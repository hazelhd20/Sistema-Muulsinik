<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseAllocation;
use App\Models\Project;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function getOverviewData(Carbon $dateFrom, ?string $projectFilter = null, int $limit = 5): array
    {
        // 1. Calcular el total de gastos del período (Directos + Requisiciones Aprobadas + Distribuidos si hay proyecto)
        if ($projectFilter) {
            $direct = (float) Expense::where('project_id', $projectFilter)
                ->where('date', '>=', $dateFrom)
                ->sum('amount');

            $distributed = (float) ExpenseAllocation::where('project_id', $projectFilter)
                ->whereHas('expense', fn ($q) => $q->where('date', '>=', $dateFrom))
                ->sum('amount');

            $requisitions = (float) Requisition::where('project_id', $projectFilter)
                ->where('status', 'aprobada')
                ->where('created_at', '>=', $dateFrom)
                ->sum('cached_total');

            $totalExpenses = $direct + $distributed + $requisitions;

            // Contador de transacciones del período (gastos directos asociados)
            $expenseCount = Expense::where('project_id', $projectFilter)
                ->where('date', '>=', $dateFrom)
                ->count();
        } else {
            $direct = (float) Expense::where('date', '>=', $dateFrom)->sum('amount');

            $requisitions = (float) Requisition::where('status', 'aprobada')
                ->where('created_at', '>=', $dateFrom)
                ->sum('cached_total');

            $totalExpenses = $direct + $requisitions;

            $expenseCount = Expense::where('date', '>=', $dateFrom)->count();
        }

        $avgExpense = $expenseCount > 0 ? $totalExpenses / $expenseCount : 0;

        $totalProjects = Project::count();
        $activeProjects = Project::where('status', 'activo')->count();
        $totalSuppliers = Supplier::count();

        $requisitionsApproved = Requisition::where('status', 'aprobada')
            ->where('created_at', '>=', $dateFrom)
            ->when($projectFilter, fn ($q) => $q->where('project_id', $projectFilter))
            ->count();
        $requisitionsPending = Requisition::where('status', 'pendiente')
            ->when($projectFilter, fn ($q) => $q->where('project_id', $projectFilter))
            ->count();

        // Gastos por categoría
        if ($projectFilter) {
            // Gastos directos del proyecto
            $directExpenses = Expense::select('category', DB::raw('SUM(amount) as total'))
                ->where('project_id', $projectFilter)
                ->where('date', '>=', $dateFrom)
                ->groupBy('category')
                ->get();

            // Gastos distribuidos (prorrateados) asignados al proyecto
            $allocatedExpenses = ExpenseAllocation::select('expenses.category', DB::raw('SUM(expense_allocations.amount) as total'))
                ->join('expenses', 'expenses.id', '=', 'expense_allocations.expense_id')
                ->where('expense_allocations.project_id', $projectFilter)
                ->where('expenses.date', '>=', $dateFrom)
                ->groupBy('expenses.category')
                ->get();

            // Unificar por categoría en PHP
            $categoriesMerged = [];
            foreach ($directExpenses as $de) {
                $categoriesMerged[$de->category] = (float) $de->total;
            }
            foreach ($allocatedExpenses as $ae) {
                $categoriesMerged[$ae->category] = ($categoriesMerged[$ae->category] ?? 0.0) + (float) $ae->total;
            }

            $expenseByCategory = collect($categoriesMerged)->map(function ($total, $category) {
                return (object) [
                    'category' => $category,
                    'total' => $total,
                ];
            })->sortByDesc('total')->values();
        } else {
            $expenseByCategory = Expense::select('category', DB::raw('SUM(amount) as total'))
                ->where('date', '>=', $dateFrom)
                ->groupBy('category')
                ->orderByDesc('total')
                ->get();
        }

        // Gastos mensuales (últimos 12 meses) - Optimización de Consultas N+1
        $startOfPeriod = now()->subMonths(11)->startOfMonth();
        $endOfPeriod = now()->endOfMonth();

        if ($projectFilter) {
            $directExpenses = Expense::where('project_id', $projectFilter)
                ->whereBetween('date', [$startOfPeriod, $endOfPeriod])
                ->select('date', 'amount')
                ->get();

            $allocatedExpenses = ExpenseAllocation::where('project_id', $projectFilter)
                ->whereHas('expense', fn ($q) => $q->whereBetween('date', [$startOfPeriod, $endOfPeriod]))
                ->with('expense:id,date')
                ->get();

            $requisitions = Requisition::where('project_id', $projectFilter)
                ->where('status', 'aprobada')
                ->whereBetween('created_at', [$startOfPeriod, $endOfPeriod])
                ->select('created_at', 'cached_total')
                ->get();
        } else {
            $directExpenses = Expense::whereBetween('date', [$startOfPeriod, $endOfPeriod])
                ->select('date', 'amount')
                ->get();

            $allocatedExpenses = collect();

            $requisitions = Requisition::where('status', 'aprobada')
                ->whereBetween('created_at', [$startOfPeriod, $endOfPeriod])
                ->select('created_at', 'cached_total')
                ->get();
        }

        // Agrupar en memoria por 'Y-m' para suma rápida
        $directGrouped = $directExpenses->groupBy(fn($e) => $e->date ? Carbon::parse($e->date)->format('Y-m') : '');
        $allocatedGrouped = $allocatedExpenses->groupBy(fn($a) => ($a->expense && $a->expense->date) ? Carbon::parse($a->expense->date)->format('Y-m') : '');
        $requisitionsGrouped = $requisitions->groupBy(fn($r) => $r->created_at ? Carbon::parse($r->created_at)->format('Y-m') : '');

        $monthlyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');

            $directMonth = (float) ($directGrouped->get($key)?->sum('amount') ?? 0.0);
            $requisitionsMonth = (float) ($requisitionsGrouped->get($key)?->sum('cached_total') ?? 0.0);

            if ($projectFilter) {
                $distributedMonth = (float) ($allocatedGrouped->get($key)?->sum('amount') ?? 0.0);
                $monthTotal = $directMonth + $distributedMonth + $requisitionsMonth;
            } else {
                $monthTotal = $directMonth + $requisitionsMonth;
            }

            $monthlyData[] = [
                'month' => $date->translatedFormat('M Y'),
                'short' => $date->translatedFormat('M'),
                'total' => $monthTotal,
            ];
        }

        // Top 5 proyectos por gasto - Optimización de Consultas N+1
        $directSpentGrouped = Expense::where('date', '>=', $dateFrom)
            ->groupBy('project_id')
            ->select('project_id', DB::raw('SUM(amount) as total'))
            ->pluck('total', 'project_id');

        $distributedSpentGrouped = ExpenseAllocation::whereHas('expense', fn ($q) => $q->where('date', '>=', $dateFrom))
            ->groupBy('project_id')
            ->select('project_id', DB::raw('SUM(amount) as total'))
            ->pluck('total', 'project_id');

        $requisitionsSpentGrouped = Requisition::where('status', 'aprobada')
            ->where('created_at', '>=', $dateFrom)
            ->groupBy('project_id')
            ->select('project_id', DB::raw('SUM(cached_total) as total'))
            ->pluck('total', 'project_id');

        $topProjects = Project::with('client')->get()
            ->map(function ($proj) use ($directSpentGrouped, $distributedSpentGrouped, $requisitionsSpentGrouped) {
                $direct = (float) ($directSpentGrouped[$proj->id] ?? 0.0);
                $distributed = (float) ($distributedSpentGrouped[$proj->id] ?? 0.0);
                $requisitions = (float) ($requisitionsSpentGrouped[$proj->id] ?? 0.0);
                $proj->total_spent = $direct + $distributed + $requisitions;
                return $proj;
            })
            ->sortByDesc('total_spent');
        if ($limit > 0) {
            $topProjects = $topProjects->take($limit);
        }
        $topProjects = $topProjects->values();

        // Presupuesto vs Gasto por proyecto
        $budgetComparison = Project::where('status', 'activo')
            ->get()
            ->map(fn ($p) => [
                'name' => $p->name,
                'budget' => (float) $p->budget,
                'spent' => (float) $p->total_expenses,
                'percent' => $p->budget_used_percent,
            ])
            ->values();

        return compact(
            'totalExpenses',
            'expenseCount',
            'avgExpense',
            'totalProjects',
            'activeProjects',
            'totalSuppliers',
            'requisitionsApproved',
            'requisitionsPending',
            'expenseByCategory',
            'monthlyData',
            'topProjects',
            'budgetComparison'
        );

    }

    public function getSupplierData(Carbon $dateFrom, ?string $projectFilter = null, int $limit = 10): array
    {
        $topSuppliers = Supplier::select('suppliers.id', 'suppliers.trade_name', 'suppliers.category')
            ->selectRaw('COUNT(DISTINCT requisitions.id) as total_requisitions')
            ->selectRaw('COUNT(requisition_items.id) as total_items')
            ->selectRaw('COALESCE(SUM(COALESCE(requisition_items.line_total, (requisition_items.unit_price * requisition_items.quantity) + COALESCE(requisition_items.tax_amount, 0))), 0) as total_amount')
            ->join('vendors', 'vendors.supplier_id', '=', 'suppliers.id')
            ->join('requisitions', 'requisitions.vendor_id', '=', 'vendors.id')
            ->join('requisition_items', 'requisition_items.requisition_id', '=', 'requisitions.id')
            ->where('requisitions.status', 'aprobada')
            ->where('requisitions.created_at', '>=', $dateFrom)
            ->when($projectFilter, fn ($q) => $q->where('requisitions.project_id', $projectFilter))
            ->groupBy('suppliers.id', 'suppliers.trade_name', 'suppliers.category')
            ->orderByDesc('total_amount')
            ->when($limit > 0, fn ($q) => $q->take($limit))
            ->get();

        return compact('topSuppliers');
    }

    public function getVendorData(Carbon $dateFrom, ?string $projectFilter = null, int $limit = 10): array
    {
        $topVendors = DB::table('vendors')
            ->select(
                'vendors.id',
                'vendors.name as vendor_name',
                'suppliers.trade_name as supplier_name',
            )
            ->selectRaw('COUNT(DISTINCT requisitions.id) as total_requisitions')
            ->selectRaw('COALESCE(SUM(COALESCE(requisition_items.line_total, (requisition_items.unit_price * requisition_items.quantity) + COALESCE(requisition_items.tax_amount, 0))), 0) as total_amount')
            ->join('suppliers', 'suppliers.id', '=', 'vendors.supplier_id')
            ->join('requisitions', 'requisitions.vendor_id', '=', 'vendors.id')
            ->join('requisition_items', 'requisition_items.requisition_id', '=', 'requisitions.id')
            ->where('requisitions.status', 'aprobada')
            ->where('requisitions.created_at', '>=', $dateFrom)
            ->when($projectFilter, fn ($q) => $q->where('requisitions.project_id', $projectFilter))
            ->groupBy('vendors.id', 'vendors.name', 'suppliers.trade_name')
            ->orderByDesc('total_amount')
            ->when($limit > 0, fn ($q) => $q->take($limit))
            ->get();

        return compact('topVendors');
    }

    public function getProductData(Carbon $dateFrom, ?string $projectFilter = null, int $limit = 15): array
    {
        $topProducts = DB::table('requisition_items')
            ->select(
                'products.id',
                'products.canonical_name',
                'categories.name as category_name',
                'measures.abbreviation as measure_abbr',
            )
            ->selectRaw('COUNT(requisition_items.id) as times_purchased')
            ->selectRaw('COALESCE(SUM(requisition_items.quantity), 0) as total_quantity')
            ->selectRaw('COALESCE(AVG(requisition_items.unit_price), 0) as avg_price')
            ->selectRaw('COALESCE(SUM(COALESCE(requisition_items.line_total, (requisition_items.unit_price * requisition_items.quantity) + COALESCE(requisition_items.tax_amount, 0))), 0) as total_amount')
            ->join('requisitions', 'requisitions.id', '=', 'requisition_items.requisition_id')
            ->join('products', 'products.id', '=', 'requisition_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('measures', 'measures.id', '=', 'requisition_items.measure_id')
            ->where('requisitions.status', 'aprobada')
            ->where('requisitions.created_at', '>=', $dateFrom)
            ->when($projectFilter, fn ($q) => $q->where('requisitions.project_id', $projectFilter))
            ->groupBy('products.id', 'products.canonical_name', 'categories.name', 'measures.abbreviation')
            ->orderByDesc('total_amount')
            ->when($limit > 0, fn ($q) => $q->take($limit))
            ->get();

        // Productos por categoría
        $productsByCategory = DB::table('requisition_items')
            ->select('categories.name as category_name')
            ->selectRaw('COALESCE(SUM(COALESCE(requisition_items.line_total, (requisition_items.unit_price * requisition_items.quantity) + COALESCE(requisition_items.tax_amount, 0))), 0) as total_amount')
            ->join('requisitions', 'requisitions.id', '=', 'requisition_items.requisition_id')
            ->join('products', 'products.id', '=', 'requisition_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('requisitions.status', 'aprobada')
            ->where('requisitions.created_at', '>=', $dateFrom)
            ->when($projectFilter, fn ($q) => $q->where('requisitions.project_id', $projectFilter))
            ->groupBy('categories.name')
            ->orderByDesc('total_amount')
            ->get();

        return compact('topProducts', 'productsByCategory');
    }
}
