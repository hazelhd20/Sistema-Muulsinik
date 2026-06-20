<?php

namespace App\Livewire\Expenses;

use App\DTOs\ExpenseDTO;
use App\Livewire\Concerns\EnforcesPermissions;
use App\Livewire\Concerns\WithSorting;
use App\Models\Expense;
use App\Models\Project;
use App\Models\User;
use App\Repositories\ExpenseRepository;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ExpenseIndex extends Component
{
    use EnforcesPermissions, WithFileUploads, WithPagination, WithSorting;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $projectFilter = '';

    #[Url(history: true)]
    public string $categoryFilter = '';

    #[Url(history: true)]
    public string $periodFilter = '';

    #[Url(history: true)]
    public string $dateFrom = '';

    #[Url(history: true)]
    public string $dateTo = '';

    #[Url(history: true)]
    public string $userFilter = '';

    public array $selectedRows = [];

    public bool $allSelected = false;

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
        $this->selectedRows = [];
        $this->allSelected = false;
    }

    public function updatedProjectFilter(): void
    {
        $this->resetPage();
        $this->selectedRows = [];
        $this->allSelected = false;
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
        $this->selectedRows = [];
        $this->allSelected = false;
    }

    public function updatedPeriodFilter(): void
    {
        $this->resetPage();
        $this->selectedRows = [];
        $this->allSelected = false;
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
        $this->selectedRows = [];
        $this->allSelected = false;
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
        $this->selectedRows = [];
        $this->allSelected = false;
    }

    public function clearAllFilters(): void
    {
        $this->reset(['search', 'projectFilter', 'categoryFilter', 'periodFilter', 'userFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
        $this->selectedRows = [];
        $this->allSelected = false;
    }

    public function mount(): void
    {
        $this->sortField = 'date';
        $this->sortDirection = 'desc';
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->date = now()->format('Y-m-d');
        $this->showCreateModal = true;
    }

    public bool $isDistributed = false;

    public function createExpense(ExpenseRepository $repository): void
    {
        if ($this->denyUnless('gastos.crear', 'No tienes permiso para registrar gastos.')) {
            return;
        }

        $validated = $this->validate([
            'concept' => 'required|min:3|max:255',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'category' => 'required',
            'projectId' => $this->isDistributed ? 'nullable' : 'required|exists:projects,id',
            'receiptFile' => 'nullable|file|max:20480|mimes:jpg,jpeg,png,pdf',
        ]);

        try {
            $dto = ExpenseDTO::fromArray([
                'concept' => $this->concept,
                'amount' => $this->amount,
                'date' => $this->date,
                'category' => $this->category,
                'projectId' => $this->projectId,
                'isDistributed' => $this->isDistributed,
                'receiptFile' => $this->receiptFile,
            ], auth()->id());

            $repository->create($dto);

            $this->showCreateModal = false;
            $this->resetForm();
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Gasto registrado correctamente.']);
        } catch (\Exception $e) {
            Log::error('Error creating expense: ' . $e->getMessage());
            $this->dispatch('toast', ['icon' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function deleteExpense(int $expenseId, ExpenseRepository $repository): void
    {
        if ($this->denyUnless('gastos.eliminar', 'No tienes permiso para eliminar gastos.')) {
            return;
        }

        $repository->delete($expenseId);
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Gasto eliminado.']);
        $this->selectedRows = array_diff($this->selectedRows, [$expenseId]);
    }

    public function toggleAll($expenseIds): void
    {
        if ($this->allSelected) {
            $this->selectedRows = array_merge($this->selectedRows, $expenseIds);
            $this->selectedRows = array_unique($this->selectedRows);
        } else {
            $this->selectedRows = array_diff($this->selectedRows, $expenseIds);
        }
    }

    public function bulkDelete(ExpenseRepository $repository): void
    {
        if ($this->denyUnless('gastos.eliminar', 'No tienes permiso para eliminar gastos.')) {
            return;
        }

        if (empty($this->selectedRows)) {
            return;
        }

        $repository->bulkDelete($this->selectedRows);

        if (count($this->selectedRows) > 0) {
            $this->dispatch('toast', ['icon' => 'success', 'message' => count($this->selectedRows) . ' gasto(s) eliminado(s) exitosamente.']);
        }

        $this->selectedRows = [];
        $this->allSelected = false;
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
            ->when($this->search, fn ($q) => $q->where('concept', 'ilike', "%{$this->search}%"))
            ->when($this->projectFilter, fn ($q) => $q->where('project_id', $this->projectFilter))
            ->when($this->categoryFilter, fn ($q) => $q->where('category', $this->categoryFilter))
            ->when($this->userFilter, fn ($q) => $q->where('user_id', $this->userFilter))
            ->when($this->periodFilter, function ($q) {
                $now = now();
                if ($this->periodFilter === 'custom') {
                    if ($this->dateFrom) {
                        $q->where('date', '>=', $this->dateFrom . ' 00:00:00');
                    }
                    if ($this->dateTo) {
                        $q->where('date', '<=', $this->dateTo . ' 23:59:59');
                    }
                    return $q;
                }
                return match ($this->periodFilter) {
                    'this_month' => $q->whereMonth('date', $now->month)->whereYear('date', $now->year),
                    'last_month' => $q->whereMonth('date', $now->subMonth()->month)->whereYear('date', $now->subMonth()->year),
                    'this_quarter' => $q->whereBetween('date', [$now->copy()->firstOfQuarter(), $now->copy()->lastOfQuarter()]),
                    'this_year' => $q->whereYear('date', $now->year),
                    default => $q,
                };
            })
            ->when(true, function ($q) {
                $dir = strtolower($this->sortDirection) === 'asc' ? 'asc' : 'desc';
                if (in_array($this->sortField, ['date', 'created_at'])) {
                    $q->orderByRaw("\"{$this->sortField}\" $dir NULLS LAST");
                } else {
                    $q->orderBy($this->sortField, $dir);
                }
            })
            ->paginate(15);

        $projects = Project::where('status', 'activo')->orderBy('name')->get();
        $categories = $this->categories;
        $users = User::select('id', 'name')->orderBy('name')->get();

        $totalMonth = Expense::whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');

        return view('livewire.expenses.expense-index', compact(
            'expenses', 'projects', 'categories', 'users', 'totalMonth'
        ));
    }
}
