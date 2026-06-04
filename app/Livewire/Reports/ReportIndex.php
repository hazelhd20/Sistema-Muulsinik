<?php

namespace App\Livewire\Reports;

use App\Models\Expense;
use App\Models\Project;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class ReportIndex extends Component
{
    public string $period = 'month';
    public string $projectFilter = '';
    public string $activeTab = 'overview';

    public function updatedPeriod(): void
    { /* triggers re-render */
    }
    public function updatedProjectFilter(): void
    { /* triggers re-render */
    }

    private function getDateFrom(): \Carbon\Carbon
    {
        return match ($this->period) {
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'quarter' => now()->subQuarter(),
            'year' => now()->subYear(),
            'all' => now()->subYears(10),
            default => now()->subMonth(),
        };
    }

    private function getCategoryLabels(): array
    {
        return [
            'materiales' => 'Materiales',
            'mano_de_obra' => 'Mano de Obra',
            'equipo' => 'Equipo y Maquinaria',
            'transporte' => 'Transporte',
            'servicios' => 'Servicios Profesionales',
            'administrativos' => 'Administrativos',
            'otros' => 'Otros',
        ];
    }

    /** Datos para la pestaña de Resumen General */
    /** Datos para la pestaña de Resumen General */
    private function getOverviewData(\Carbon\Carbon $dateFrom): array
    {
        // 1. Calcular el total de gastos del período (Directos + Requisiciones Aprobadas + Distribuidos si hay proyecto)
        if ($this->projectFilter) {
            $direct = (float) Expense::where('project_id', $this->projectFilter)
                ->where('date', '>=', $dateFrom)
                ->sum('amount');

            $distributed = (float) \App\Models\ExpenseAllocation::where('project_id', $this->projectFilter)
                ->whereHas('expense', fn($q) => $q->where('date', '>=', $dateFrom))
                ->sum('amount');

            $requisitions = (float) Requisition::where('project_id', $this->projectFilter)
                ->where('status', 'aprobada')
                ->where('created_at', '>=', $dateFrom)
                ->with('items')
                ->get()
                ->sum(fn($req) => $req->total);

            $totalExpenses = $direct + $distributed + $requisitions;

            // Contador de transacciones del período (gastos directos asociados)
            $expenseCount = Expense::where('project_id', $this->projectFilter)
                ->where('date', '>=', $dateFrom)
                ->count();
        } else {
            $direct = (float) Expense::where('date', '>=', $dateFrom)->sum('amount');

            $requisitions = (float) Requisition::where('status', 'aprobada')
                ->where('created_at', '>=', $dateFrom)
                ->with('items')
                ->get()
                ->sum(fn($req) => $req->total);

            $totalExpenses = $direct + $requisitions;

            $expenseCount = Expense::where('date', '>=', $dateFrom)->count();
        }

        $avgExpense = $expenseCount > 0 ? $totalExpenses / $expenseCount : 0;

        $totalProjects = Project::count();
        $activeProjects = Project::where('status', 'activo')->count();
        $totalSuppliers = Supplier::count();

        $requisitionsApproved = Requisition::where('status', 'aprobada')
            ->where('created_at', '>=', $dateFrom)
            ->when($this->projectFilter, fn($q) => $q->where('project_id', $this->projectFilter))
            ->count();
        $requisitionsPending = Requisition::where('status', 'pendiente')
            ->when($this->projectFilter, fn($q) => $q->where('project_id', $this->projectFilter))
            ->count();

        // Gastos por categoría
        if ($this->projectFilter) {
            // Gastos directos del proyecto
            $directExpenses = Expense::select('category', DB::raw('SUM(amount) as total'))
                ->where('project_id', $this->projectFilter)
                ->where('date', '>=', $dateFrom)
                ->groupBy('category')
                ->get();

            // Gastos distribuidos (prorrateados) asignados al proyecto
            $allocatedExpenses = \App\Models\ExpenseAllocation::select('expenses.category', DB::raw('SUM(expense_allocations.amount) as total'))
                ->join('expenses', 'expenses.id', '=', 'expense_allocations.expense_id')
                ->where('expense_allocations.project_id', $this->projectFilter)
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
            })->sortByDesc('total');
        } else {
            $expenseByCategory = Expense::select('category', DB::raw('SUM(amount) as total'))
                ->where('date', '>=', $dateFrom)
                ->groupBy('category')
                ->orderByDesc('total')
                ->get();
        }

        // Gastos mensuales (últimos 12 meses) (Directo + Distribuido + Requisiciones Aprobadas)
        $monthlyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);

            if ($this->projectFilter) {
                $directMonth = (float) Expense::where('project_id', $this->projectFilter)
                    ->whereMonth('date', $date->month)
                    ->whereYear('date', $date->year)
                    ->sum('amount');

                $distributedMonth = (float) \App\Models\ExpenseAllocation::where('project_id', $this->projectFilter)
                    ->whereHas('expense', fn($q) => $q->whereMonth('date', $date->month)->whereYear('date', $date->year))
                    ->sum('amount');

                $requisitionsMonth = (float) Requisition::where('project_id', $this->projectFilter)
                    ->where('status', 'aprobada')
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->with('items')
                    ->get()
                    ->sum(fn($req) => $req->total);

                $monthTotal = $directMonth + $distributedMonth + $requisitionsMonth;
            } else {
                $directMonth = (float) Expense::whereMonth('date', $date->month)
                    ->whereYear('date', $date->year)
                    ->sum('amount');

                $requisitionsMonth = (float) Requisition::where('status', 'aprobada')
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->with('items')
                    ->get()
                    ->sum(fn($req) => $req->total);

                $monthTotal = $directMonth + $requisitionsMonth;
            }

            $monthlyData[] = [
                'month' => $date->translatedFormat('M Y'),
                'short' => $date->translatedFormat('M'),
                'total' => $monthTotal,
            ];
        }

        // Top 5 proyectos por gasto (Directo + Distribuido + Requisiciones Aprobadas)
        $topProjects = Project::all()
            ->map(function ($proj) use ($dateFrom) {
                $proj->total_spent = $proj->getSpentInPeriod($dateFrom);
                return $proj;
            })
            ->sortByDesc('total_spent')
            ->take(5);

        // Presupuesto vs Gasto por proyecto
        $budgetComparison = Project::where('status', 'activo')
            ->get()
            ->map(fn($p) => [
                'name' => $p->name,
                'budget' => (float) $p->budget,
                'spent' => (float) $p->total_expenses,
                'percent' => $p->budget_used_percent,
            ]);

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

    /** Datos para la pestaña de Compras por Proveedor */
    private function getSupplierData(\Carbon\Carbon $dateFrom): array
    {
        // Top proveedores por monto total de requisiciones aprobadas
        $topSuppliers = Supplier::select('suppliers.id', 'suppliers.trade_name', 'suppliers.category')
            ->selectRaw('COUNT(DISTINCT requisitions.id) as total_requisitions')
            ->selectRaw('COUNT(requisition_items.id) as total_items')
            ->selectRaw('COALESCE(SUM(COALESCE(requisition_items.line_total, (requisition_items.unit_price * requisition_items.quantity) + COALESCE(requisition_items.tax_amount, 0))), 0) as total_amount')
            ->join('vendors', 'vendors.supplier_id', '=', 'suppliers.id')
            ->join('requisitions', 'requisitions.vendor_id', '=', 'vendors.id')
            ->join('requisition_items', 'requisition_items.requisition_id', '=', 'requisitions.id')
            ->where('requisitions.status', 'aprobada')
            ->where('requisitions.created_at', '>=', $dateFrom)
            ->when($this->projectFilter, fn($q) => $q->where('requisitions.project_id', $this->projectFilter))
            ->groupBy('suppliers.id', 'suppliers.trade_name', 'suppliers.category')
            ->orderByDesc('total_amount')
            ->take(10)
            ->get();

        return compact('topSuppliers');
    }

    /** Datos para la pestaña de Compras por Vendedor */
    private function getVendorData(\Carbon\Carbon $dateFrom): array
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
            ->when($this->projectFilter, fn($q) => $q->where('requisitions.project_id', $this->projectFilter))
            ->groupBy('vendors.id', 'vendors.name', 'suppliers.trade_name')
            ->orderByDesc('total_amount')
            ->take(10)
            ->get();

        return compact('topVendors');
    }

    /** Datos para la pestaña de Productos Más Comprados */
    private function getProductData(\Carbon\Carbon $dateFrom): array
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
            ->when($this->projectFilter, fn($q) => $q->where('requisitions.project_id', $this->projectFilter))
            ->groupBy('products.id', 'products.canonical_name', 'categories.name', 'measures.abbreviation')
            ->orderByDesc('total_amount')
            ->take(15)
            ->get();

        // Productos por categoría (para gráfica de donut)
        $productsByCategory = DB::table('requisition_items')
            ->select('categories.name as category_name')
            ->selectRaw('COALESCE(SUM(COALESCE(requisition_items.line_total, (requisition_items.unit_price * requisition_items.quantity) + COALESCE(requisition_items.tax_amount, 0))), 0) as total_amount')
            ->join('requisitions', 'requisitions.id', '=', 'requisition_items.requisition_id')
            ->join('products', 'products.id', '=', 'requisition_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('requisitions.status', 'aprobada')
            ->where('requisitions.created_at', '>=', $dateFrom)
            ->when($this->projectFilter, fn($q) => $q->where('requisitions.project_id', $this->projectFilter))
            ->groupBy('categories.name')
            ->orderByDesc('total_amount')
            ->get();

        return compact('topProducts', 'productsByCategory');
    }

    #[Layout('components.layouts.app')]
    #[Title('Reportes')]
    public function render()
    {
        $dateFrom = $this->getDateFrom();
        $categoryLabels = $this->getCategoryLabels();
        $projects = Project::orderBy('name')->get();

        // Cargar los datos según la pestaña activa para rendimiento
        $overviewData = $this->getOverviewData($dateFrom);
        $supplierData = $this->getSupplierData($dateFrom);
        $vendorData = $this->getVendorData($dateFrom);
        $productData = $this->getProductData($dateFrom);

        return view('livewire.reports.report-index', array_merge(
            $overviewData,
            $supplierData,
            $vendorData,
            $productData,
            compact('projects', 'categoryLabels')
        ));
    }
}
