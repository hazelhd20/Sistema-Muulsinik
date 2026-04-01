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
        $totalExpenses = Expense::sum('amount');
        $monthExpenses = Expense::whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');
        $pendingRequisitions = Requisition::where('status', 'pendiente')->count();
        $totalSuppliers = Supplier::count();

        $recentProjects = Project::latest()->take(5)->get();
        $recentExpenses = Expense::with(['project', 'user'])->latest()->take(5)->get();
        $recentRequisitions = Requisition::with(['project', 'creator'])->latest()->take(5)->get();

        // Datos para gráfico de gastos mensuales (últimos 6 meses)
        $monthlyExpenses = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthlyExpenses[] = [
                'month' => $date->translatedFormat('M'),
                'total' => (float) Expense::whereMonth('date', $date->month)
                    ->whereYear('date', $date->year)
                    ->sum('amount'),
            ];
        }

        return view('livewire.dashboard', compact(
            'totalProjects', 'activeProjects', 'totalExpenses',
            'monthExpenses', 'pendingRequisitions', 'totalSuppliers',
            'recentProjects', 'recentExpenses', 'recentRequisitions',
            'monthlyExpenses'
        ));
    }
}
