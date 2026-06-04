<?php

namespace App\Livewire;

use App\Models\Expense;
use App\Models\Project;
use App\Models\Requisition;
use App\Models\Supplier;
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
        $requisitionsTotalAllTime = (float) Requisition::where('status', 'aprobada')
            ->with('items')
            ->get()
            ->sum(fn($req) => $req->total);
        $totalExpenses = (float) Expense::sum('amount') + $requisitionsTotalAllTime;

        $requisitionsTotalThisMonth = (float) Requisition::where('status', 'aprobada')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->with('items')
            ->get()
            ->sum(fn($req) => $req->total);
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
        $monthlyExpenses = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            
            $direct = (float) Expense::whereMonth('date', $date->month)
                ->whereYear('date', $date->year)
                ->sum('amount');
                
            $requisitions = (float) Requisition::where('status', 'aprobada')
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->with('items')
                ->get()
                ->sum(fn($req) => $req->total);

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
