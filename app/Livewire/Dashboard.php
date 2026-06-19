<?php

namespace App\Livewire;

use App\Models\Expense;
use App\Models\Project;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Dashboard extends Component
{
    #[Layout('components.layouts.app')]
    #[Title('Dashboard')]
    public function render()
    {
        $totalProjects = Project::count();
        $activeProjects = Project::where('status', 'activo')->count();
        $requisitionsTotalAllTime = (float) RequisitionItem::join('requisitions', 'requisitions.id', '=', 'requisition_items.requisition_id')
            ->where('requisitions.status', 'aprobada')
            ->sum(DB::raw('COALESCE(requisition_items.line_total, (requisition_items.unit_price * requisition_items.quantity) + COALESCE(requisition_items.tax_amount, 0))'));
        $totalExpenses = (float) Expense::sum('amount') + $requisitionsTotalAllTime;

        $requisitionsTotalThisMonth = (float) RequisitionItem::join('requisitions', 'requisitions.id', '=', 'requisition_items.requisition_id')
            ->where('requisitions.status', 'aprobada')
            ->whereMonth('requisitions.created_at', now()->month)
            ->whereYear('requisitions.created_at', now()->year)
            ->sum(DB::raw('COALESCE(requisition_items.line_total, (requisition_items.unit_price * requisition_items.quantity) + COALESCE(requisition_items.tax_amount, 0))'));
        $monthExpenses = (float) Expense::whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount') + $requisitionsTotalThisMonth;

        $pendingRequisitions = Requisition::where('status', 'pendiente')->count();
        $approvedRequisitions = Requisition::where('status', 'aprobada')->count();
        $totalSuppliers = Supplier::count();

        $recentProjects = Project::latest()->take(5)->get();
        $recentExpenses = Expense::with(['project', 'user'])->latest()->take(5)->get();
        $recentRequisitions = Requisition::with(['project', 'creator'])->latest()->take(5)->get();

        // Datos para gráfico de gastos mensuales (últimos 6 meses) (Directo + Requisiciones Aprobadas)
        $startDate = now()->subMonths(5)->startOfMonth();
        $endDate = now()->endOfMonth();

        $directExpenses = Expense::selectRaw('DATE_TRUNC(\'month\', date) as month_date, SUM(amount) as total')
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE_TRUNC(\'month\', date)'))
            ->get()
            ->keyBy(fn($item) => \Carbon\Carbon::parse($item->month_date)->format('Y-m'));

        $requisitionExpenses = RequisitionItem::join('requisitions', 'requisitions.id', '=', 'requisition_items.requisition_id')
            ->selectRaw('DATE_TRUNC(\'month\', requisitions.created_at) as month_date, SUM(COALESCE(requisition_items.line_total, (requisition_items.unit_price * requisition_items.quantity) + COALESCE(requisition_items.tax_amount, 0))) as total')
            ->where('requisitions.status', 'aprobada')
            ->whereBetween('requisitions.created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE_TRUNC(\'month\', requisitions.created_at)'))
            ->get()
            ->keyBy(fn($item) => \Carbon\Carbon::parse($item->month_date)->format('Y-m'));

        $monthlyExpenses = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');

            $direct = (float) ($directExpenses[$key]->total ?? 0);
            $requisitions = (float) ($requisitionExpenses[$key]->total ?? 0);

            $monthlyExpenses[] = [
                'month' => $date->translatedFormat('M'),
                'total' => $direct + $requisitions,
            ];
        }

        return view('livewire.dashboard', compact(
            'totalProjects', 'activeProjects', 'totalExpenses',
            'monthExpenses', 'pendingRequisitions', 'approvedRequisitions', 'totalSuppliers',
            'recentProjects', 'recentExpenses', 'recentRequisitions',
            'monthlyExpenses'
        ));
    }
}
