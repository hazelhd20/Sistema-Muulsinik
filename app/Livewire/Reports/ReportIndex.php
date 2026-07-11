<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\EnforcesPermissions;
use App\Models\Expense;
use App\Models\ExpenseAllocation;
use App\Models\Project;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

class ReportIndex extends Component
{
    use EnforcesPermissions;

    public function mount(): void
    {
        if (! auth()->user()?->hasPermission('reportes.ver') && ! auth()->user()?->hasPermission('*')) {
            abort(403, 'No tienes permiso para ver reportes.');
        }
    }

    #[Url(history: true)]
    public string $period = 'month';

    #[Url(history: true)]
    public string $projectFilter = '';

    #[Url(history: true)]
    public string $activeTab = 'overview';

    public function updatedPeriod(): void
    { /* triggers re-render */
    }

    public function updatedProjectFilter(): void
    { /* triggers re-render */
    }

    private function getDateFrom(): Carbon
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


    public function exportCsv()
    {
        if ($this->denyUnless('reportes.ver')) {
            return null;
        }

        // Redirige al Controller HTTP dedicado con los filtros actuales del componente.
        // Navigate: false garantiza que el navegador descargue el CSV sin que Livewire intercepte la respuesta.
        $this->redirect(route('reportes.export.csv', [
            'tab'            => $this->activeTab,
            'period'         => $this->period,
            'project_filter' => $this->projectFilter ?: null,
        ]), navigate: false);
    }

    public function exportExcel()
    {
        if ($this->denyUnless('reportes.ver')) {
            return null;
        }

        $this->redirect(route('reportes.export.excel', [
            'tab'            => $this->activeTab,
            'period'         => $this->period,
            'project_filter' => $this->projectFilter ?: null,
        ]), navigate: false);
    }

    /** Datos para la pestaña de Resumen General */


    #[Layout('components.layouts.app')]
    #[Title('Reportes')]
    public function render(\App\Services\ReportService $reportService)
    {
        $dateFrom = $this->getDateFrom();
        $categoryLabels = \App\Enums\ExpenseCategory::toArray();
        $projects = Project::orderBy('name')->get();

        // Cargar los datos según la pestaña activa para rendimiento (Lazy Loading)
        $overviewData = [
            'totalExpenses' => 0.0,
            'expenseCount' => 0,
            'avgExpense' => 0.0,
            'totalProjects' => 0,
            'activeProjects' => 0,
            'totalSuppliers' => 0,
            'requisitionsApproved' => 0,
            'requisitionsPending' => 0,
            'expenseByCategory' => collect(),
            'monthlyData' => [],
            'topProjects' => collect(),
            'budgetComparison' => collect(),
            'expensesTrend' => null,
            'transactionsTrend' => null,
            'requisitionsTrend' => null,
            'avgExpenseTrend' => null,
        ];
        $supplierData = ['topSuppliers' => collect()];
        $vendorData = ['topVendors' => collect()];
        $productData = ['topProducts' => collect(), 'productsByCategory' => collect()];

        if ($this->activeTab === 'overview') {
            $overviewData = $reportService->getOverviewData($dateFrom, $this->projectFilter);
        } elseif ($this->activeTab === 'suppliers') {
            $supplierData = $reportService->getSupplierData($dateFrom, $this->projectFilter);
        } elseif ($this->activeTab === 'vendors') {
            $vendorData = $reportService->getVendorData($dateFrom, $this->projectFilter);
        } elseif ($this->activeTab === 'products') {
            $productData = $reportService->getProductData($dateFrom, $this->projectFilter);
        }

        return view('livewire.reports.report-index', array_merge(
            $overviewData,
            $supplierData,
            $vendorData,
            $productData,
            compact('projects', 'categoryLabels')
        ));
    }
}
