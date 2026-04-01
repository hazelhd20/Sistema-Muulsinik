<?php

namespace App\Livewire\Expenses;

use App\Models\Expense;
use App\Models\Project;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ExpenseIndex extends Component
{
    use WithPagination, WithFileUploads;

    public string $search = '';
    public string $projectFilter = '';
    public string $categoryFilter = '';
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

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->date = now()->format('Y-m-d');
        $this->showCreateModal = true;
    }

    public function createExpense(): void
    {
        $this->validate([
            'concept' => 'required|min:3|max:255',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'category' => 'required',
            'projectId' => 'required|exists:projects,id',
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
            'project_id' => $this->projectId,
            'user_id' => auth()->id(),
            'receipt_file' => $receiptPath,
        ]);

        // Verificar alertas de presupuesto (RF-GASTO-03)
        $this->checkBudgetAlerts($expense->project);

        $this->showCreateModal = false;
        $this->resetForm();
        session()->flash('success', 'Gasto registrado correctamente.');
    }

    public function deleteExpense(int $expenseId): void
    {
        Expense::findOrFail($expenseId)->delete();
        session()->flash('success', 'Gasto eliminado.');
    }

    /** Verificar umbrales de presupuesto del proyecto (RF-GASTO-03). */
    private function checkBudgetAlerts(Project $project): void
    {
        $percent = $project->budget_used_percent;

        if ($percent >= 100) {
            session()->flash('budget_alert', "⚠️ ALERTA: El proyecto \"{$project->name}\" ha superado el 100% del presupuesto asignado.");
        } elseif ($percent >= 90) {
            session()->flash('budget_alert', "⚠️ PRECAUCIÓN: El proyecto \"{$project->name}\" ha alcanzado el 90% del presupuesto.");
        } elseif ($percent >= 70) {
            session()->flash('budget_alert', "📊 AVISO: El proyecto \"{$project->name}\" ha alcanzado el 70% del presupuesto.");
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
    }

    #[Layout('components.layouts.app')]
    #[Title('Gastos')]
    public function render()
    {
        $expenses = Expense::with(['project', 'user'])
            ->when($this->search, fn ($q) => $q->where('concept', 'like', "%{$this->search}%"))
            ->when($this->projectFilter, fn ($q) => $q->where('project_id', $this->projectFilter))
            ->when($this->categoryFilter, fn ($q) => $q->where('category', $this->categoryFilter))
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
