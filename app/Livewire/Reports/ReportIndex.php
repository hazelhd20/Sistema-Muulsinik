<?php

namespace App\Livewire\Reports;

use App\Models\Expense;
use App\Models\Project;
use App\Models\Requisition;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class ReportIndex extends Component
{
    public string $period = 'month';
    public string $projectFilter = '';

    #[Layout('components.layouts.app')]
    #[Title('Reportes')]
    public function render()
    {
        $dateFrom = match ($this->period) {
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'quarter' => now()->subQuarter(),
            'year' => now()->subYear(),
            'all' => now()->subYears(10),
            default => now()->subMonth(),
        };

        // === KPIs globales ===
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

        // === Gastos por categoría ===
        $expenseByCategoryQuery = Expense::select('category', DB::raw('SUM(amount) as total'))
            ->where('date', '>=', $dateFrom)
            ->groupBy('category')
            ->orderByDesc('total');

        if ($this->projectFilter) {
            $expenseByCategoryQuery->where('project_id', $this->projectFilter);
        }
        $expenseByCategory = $expenseByCategoryQuery->get();

        // === Gastos mensuales (últimos 12 meses) ===
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

        // === Top 5 proyectos por gasto ===
        $topProjects = Project::select('projects.*')
            ->selectRaw('(SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE expenses.project_id = projects.id AND expenses.date >= ?) as total_spent', [$dateFrom])
            ->orderByDesc('total_spent')
            ->take(5)
            ->get();

        // === Presupuesto vs Gasto por proyecto ===
        $budgetComparison = Project::where('status', 'activo')
            ->get()
            ->map(fn ($p) => [
                'name' => $p->name,
                'budget' => (float) $p->budget,
                'spent' => (float) $p->total_expenses,
                'percent' => $p->budget_used_percent,
            ]);

        $projects = Project::orderBy('name')->get();

        $categoryLabels = [
            'materiales' => 'Materiales',
            'mano_de_obra' => 'Mano de Obra',
            'equipo' => 'Equipo y Maquinaria',
            'transporte' => 'Transporte',
            'servicios' => 'Servicios Profesionales',
            'administrativos' => 'Administrativos',
            'otros' => 'Otros',
        ];

        return view('livewire.reports.report-index', compact(
            'totalExpenses', 'expenseCount', 'avgExpense',
            'totalProjects', 'activeProjects', 'totalSuppliers',
            'requisitionsApproved', 'requisitionsPending',
            'expenseByCategory', 'monthlyData',
            'topProjects', 'budgetComparison',
            'projects', 'categoryLabels'
        ));
    }
}
