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
        $globalStats = \Illuminate\Support\Facades\Cache::remember('dashboard_global_stats', now()->addHours(1), function () {
            return [
                'totalProjects' => Project::count(),
                'activeProjects' => Project::where('status', 'activo')->count(),
                'pendingRequisitions' => Requisition::where('status', 'pendiente')->count(),
                'approvedRequisitions' => Requisition::where('status', 'aprobada')->count(),
                'totalSuppliers' => Supplier::count(),
            ];
        });

        $financialStats = \Illuminate\Support\Facades\Cache::remember('dashboard_financial_stats', now()->addHours(1), function () {
            $requisitionsTotalAllTime = (float) Requisition::where('status', 'aprobada')->sum('cached_total');
            $totalExpenses = (float) Expense::sum('amount') + $requisitionsTotalAllTime;

            $requisitionsTotalThisMonth = (float) Requisition::where('status', 'aprobada')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('cached_total');
            $monthExpenses = (float) Expense::whereMonth('date', now()->month)
                ->whereYear('date', now()->year)
                ->sum('amount') + $requisitionsTotalThisMonth;

            return compact('totalExpenses', 'monthExpenses');
        });

        $monthlyExpenses = \Illuminate\Support\Facades\Cache::remember('dashboard_monthly_chart', now()->addHours(1), function () {
            $startDate = now()->subMonths(5)->startOfMonth();
            $endDate = now()->endOfMonth();

            $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
            $dateTruncExpense = $driver === 'sqlite' ? "strftime('%Y-%m', date)" : "DATE_TRUNC('month', date)";
            $dateTruncReq = $driver === 'sqlite' ? "strftime('%Y-%m', created_at)" : "DATE_TRUNC('month', created_at)";

            $directExpenses = Expense::selectRaw("$dateTruncExpense as month_date, SUM(amount) as total")
                ->whereBetween('date', [$startDate, $endDate])
                ->groupBy(DB::raw($dateTruncExpense))
                ->get()
                ->keyBy(fn($item) => \Carbon\Carbon::parse($item->month_date)->format('Y-m'));

            $requisitionExpenses = Requisition::selectRaw("$dateTruncReq as month_date, SUM(cached_total) as total")
                ->where('status', 'aprobada')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy(DB::raw($dateTruncReq))
                ->get()
                ->keyBy(fn($item) => \Carbon\Carbon::parse($item->month_date)->format('Y-m'));

            $chartData = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $key = $date->format('Y-m');

                $direct = (float) ($directExpenses[$key]->total ?? 0);
                $requisitions = (float) ($requisitionExpenses[$key]->total ?? 0);

                $chartData[] = [
                    'month' => $date->translatedFormat('M'),
                    'total' => $direct + $requisitions,
                ];
            }
            return $chartData;
        });

        // Estas consultas se mantienen en tiempo real por su naturaleza
        $recentProjects = Project::with('client')->latest()->take(5)->get();
        $recentExpenses = Expense::with(['project', 'user'])->latest()->take(5)->get();
        $recentRequisitions = Requisition::with(['project', 'creator'])->latest()->take(5)->get();

        return view('livewire.dashboard', [
            'totalProjects' => $globalStats['totalProjects'],
            'activeProjects' => $globalStats['activeProjects'],
            'pendingRequisitions' => $globalStats['pendingRequisitions'],
            'approvedRequisitions' => $globalStats['approvedRequisitions'],
            'totalSuppliers' => $globalStats['totalSuppliers'],
            'totalExpenses' => $financialStats['totalExpenses'],
            'monthExpenses' => $financialStats['monthExpenses'],
            'recentProjects' => $recentProjects,
            'recentExpenses' => $recentExpenses,
            'recentRequisitions' => $recentRequisitions,
            'monthlyExpenses' => $monthlyExpenses,
        ]);
    }
}
