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

    public function updatedPeriod(): void { /* triggers re-render */ }
    public function updatedProjectFilter(): void { /* triggers re-render */ }

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
    private function getOverviewData(\Carbon\Carbon $dateFrom): array
    {
        $expenseQuery = Expense::where('date', '>=', $dateFrom);
        if ($this->projectFilter) {
            $expenseQuery->where('project_id', $this->projectFilter);
        }

        $totalExpenses = (float) $expenseQuery->sum('amount');
        $expenseCount = $expenseQuery->count();
        $avgExpense = $expenseCount > 0 ? $totalExpenses / $expenseCount : 0;

        $totalProjects = Project::count();
        $activeProjects = Project::where('status', 'activo')->count();
        $totalSuppliers = Supplier::count();

        $requisitionsApproved = Requisition::where('status', 'aprobada')
            ->where('created_at', '>=', $dateFrom)
            ->count();
        $requisitionsPending = Requisition::where('status', 'pendiente')->count();

        // Gastos por categoría
        $expenseByCategoryQuery = Expense::select('category', DB::raw('SUM(amount) as total'))
            ->where('date', '>=', $dateFrom)
            ->groupBy('category')
            ->orderByDesc('total');
        if ($this->projectFilter) {
            $expenseByCategoryQuery->where('project_id', $this->projectFilter);
        }
        $expenseByCategory = $expenseByCategoryQuery->get();

        // Gastos mensuales (últimos 12 meses)
        $monthlyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $query = Expense::whereMonth('date', $date->month)
                ->whereYear('date', $date->year);
            if ($this->projectFilter) {
                $query->where('project_id', $this->projectFilter);
            }
            $monthlyData[] = [
                'month' => $date->translatedFormat('M Y'),
                'short' => $date->translatedFormat('M'),
                'total' => (float) $query->sum('amount'),
            ];
        }

        // Top 5 proyectos por gasto
        $topProjects = Project::select('projects.*')
            ->selectRaw('(SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE expenses.project_id = projects.id AND expenses.date >= ?) as total_spent', [$dateFrom])
            ->orderByDesc('total_spent')
            ->take(5)
            ->get();

        // Presupuesto vs Gasto por proyecto
        $budgetComparison = Project::where('status', 'activo')
            ->get()
            ->map(fn ($p) => [
                'name' => $p->name,
                'budget' => (float) $p->budget,
                'spent' => (float) $p->total_expenses,
                'percent' => $p->budget_used_percent,
            ]);

        return compact(
            'totalExpenses', 'expenseCount', 'avgExpense',
            'totalProjects', 'activeProjects', 'totalSuppliers',
            'requisitionsApproved', 'requisitionsPending',
            'expenseByCategory', 'monthlyData',
            'topProjects', 'budgetComparison'
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
