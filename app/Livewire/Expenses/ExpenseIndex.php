<?php

namespace App\Livewire\Expenses;

use App\Livewire\Concerns\EnforcesPermissions;
use App\Models\Expense;
use App\Models\Project;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ExpenseIndex extends Component
{
    use WithPagination, WithFileUploads, EnforcesPermissions;

    public string $search = '';
    public string $projectFilter = '';
    public string $categoryFilter = '';
    public string $periodFilter = '';
    public bool $showCreateModal = false;

    // Campos del formulario
    public string $concept = '';
    public string $amount = '';
    public string $date = '';
    public string $category = '';
    public $projectId = '';
    public $receiptFile = null;

    protected array $categories = [
        'materiales' => 'Materiales',
        'mano_de_obra' => 'Mano de Obra',
        'equipo' => 'Equipo y Maquinaria',
        'transporte' => 'Transporte',
        'servicios' => 'Servicios Profesionales',
        'administrativos' => 'Gastos Administrativos',
        'otros' => 'Otros',
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPeriodFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->date = now()->format('Y-m-d');
        $this->showCreateModal = true;
    }

    public bool $isDistributed = false;

    public function createExpense(): void
    {
        if ($this->denyUnless('gastos.crear', 'No tienes permiso para registrar gastos.')) return;

        $this->validate([
            'concept' => 'required|min:3|max:255',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'category' => 'required',
            'projectId' => $this->isDistributed ? 'nullable' : 'required|exists:projects,id',
            'receiptFile' => 'nullable|file|max:20480|mimes:jpg,jpeg,png,pdf',
        ]);

        $receiptPath = null;
        if ($this->receiptFile) {
            $receiptPath = $this->receiptFile->store('receipts', 'public');
        }

        $expense = Expense::create([
            'concept' => $this->concept,
            'amount' => $this->amount,
            'date' => $this->date,
            'category' => $this->category,
            'project_id' => $this->isDistributed ? null : $this->projectId,
            'is_distributed' => $this->isDistributed,
            'user_id' => auth()->id(),
            'receipt_file' => $receiptPath,
        ]);

        if ($this->isDistributed) {
            $activeProjects = Project::where('status', 'activo')->get();
            $count = $activeProjects->count();
            if ($count > 0) {
                $amountPerProject = round($this->amount / $count, 2);
                $percentage = round(100 / $count, 2);
                foreach ($activeProjects as $project) {
                    \App\Models\ExpenseAllocation::create([
                        'expense_id' => $expense->id,
                        'project_id' => $project->id,
                        'amount' => $amountPerProject,
                        'percentage' => $percentage,
                    ]);
                    // Verificar alertas de presupuesto para cada proyecto
                    $this->checkBudgetAlerts($project);
                }
            }
        } else {
            $this->checkBudgetAlerts($expense->project);
        }

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Gasto registrado correctamente.']);
    }

    public function deleteExpense(int $expenseId): void
    {
        if ($this->denyUnless('gastos.eliminar', 'No tienes permiso para eliminar gastos.')) return;

        Expense::findOrFail($expenseId)->delete();
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Gasto eliminado.']);
    }

    /** Verificar umbrales de presupuesto del proyecto (RF-GASTO-03). */
    private function checkBudgetAlerts(Project $project): void
    {
        $percent = $project->budget_used_percent;

        if ($percent >= 100) {
            $this->dispatch('toast', ['icon' => 'warning', 'message' => "⚠️ ALERTA: El proyecto \"{$project->name}\" ha superado el 100% del presupuesto asignado."]);
        } elseif ($percent >= 90) {
            $this->dispatch('toast', ['icon' => 'warning', 'message' => "⚠️ PRECAUCIÓN: El proyecto \"{$project->name}\" ha alcanzado el 90% del presupuesto."]);
        } elseif ($percent >= 70) {
            $this->dispatch('toast', ['icon' => 'info', 'message' => "📊 AVISO: El proyecto \"{$project->name}\" ha alcanzado el 70% del presupuesto."]);
        }
    }

    private function resetForm(): void
    {
        $this->concept = '';
        $this->amount = '';
        $this->date = '';
        $this->category = '';
        $this->projectId = '';
        $this->receiptFile = null;
        $this->isDistributed = false;
    }

    #[Layout('components.layouts.app')]
    #[Title('Gastos')]
    public function render()
    {
        $expenses = Expense::with(['project', 'user'])
            ->when($this->search, fn ($q) => $q->where('concept', 'like', "%{$this->search}%"))
            ->when($this->projectFilter, fn ($q) => $q->where('project_id', $this->projectFilter))
            ->when($this->categoryFilter, fn ($q) => $q->where('category', $this->categoryFilter))
            ->when($this->periodFilter, function ($q) {
                match ($this->periodFilter) {
                    'this_month'   => $q->whereMonth('date', now()->month)->whereYear('date', now()->year),
                    'last_month'   => $q->whereMonth('date', now()->subMonth()->month)->whereYear('date', now()->subMonth()->year),
                    'this_quarter' => $q->whereBetween('date', [now()->startOfQuarter(), now()->endOfQuarter()]),
                    'this_year'    => $q->whereYear('date', now()->year),
                    default        => null,
                };
            })
            ->latest('date')
            ->paginate(15);

        $projects = Project::where('status', 'activo')->orderBy('name')->get();
        $categories = $this->categories;

        $totalMonth = Expense::whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');

        return view('livewire.expenses.expense-index', compact(
            'expenses', 'projects', 'categories', 'totalMonth'
        ));
    }
}
