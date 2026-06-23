<?php

namespace App\Livewire\QuickBudgets;

use App\Livewire\Concerns\EnforcesPermissions;
use App\Livewire\Concerns\WithSorting;
use App\Models\QuickBudget;
use App\Repositories\QuickBudgetRepository;
use App\Enums\QuickBudgetStatus;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class QuickBudgetIndex extends Component
{
    use WithPagination, WithSorting, EnforcesPermissions;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $periodFilter = '';

    #[Url(history: true)]
    public string $dateFrom = '';

    #[Url(history: true)]
    public string $dateTo = '';

    #[Url(history: true)]
    public string $statusFilter = '';

    #[Url(history: true)]
    public string $userFilter = '';

    public array $selectedRows = [];

    public bool $allSelected = false;

    public function mount(): void
    {
        if (! auth()->user()?->hasPermission('cotizaciones.ver') &&
            ! auth()->user()?->hasPermission('*')) {
            abort(403, 'No tienes permiso para acceder al cotizador rápido.');
        }
    }

    public function updatedSearch(): void
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
        $this->reset(['search', 'periodFilter', 'statusFilter', 'userFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
        $this->selectedRows = [];
        $this->allSelected = false;
    }

    public function deleteBudget(int $id, QuickBudgetRepository $repository): void
    {
        if (! auth()->user()?->hasPermission('cotizaciones.eliminar') &&
            ! auth()->user()?->hasPermission('*')) {
            session()->flash('error', 'No tienes permiso para eliminar cotizaciones.');
            return;
        }

        $repository->delete($id);
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Cotización eliminada.']);
        $this->selectedRows = array_diff($this->selectedRows, [$id]);
    }

    public function toggleAll($budgetIds): void
    {
        if ($this->allSelected) {
            $this->selectedRows = array_merge($this->selectedRows, $budgetIds);
            $this->selectedRows = array_unique($this->selectedRows);
        } else {
            $this->selectedRows = array_diff($this->selectedRows, $budgetIds);
        }
    }

    public function bulkDelete(QuickBudgetRepository $repository): void
    {
        if (! auth()->user()?->hasPermission('cotizaciones.eliminar') &&
            ! auth()->user()?->hasPermission('*')) {
            session()->flash('error', 'No tienes permiso para eliminar cotizaciones.');
            return;
        }

        if (empty($this->selectedRows)) {
            return;
        }

        $repository->bulkDelete($this->selectedRows);

        if (count($this->selectedRows) > 0) {
            $this->dispatch('toast', ['icon' => 'success', 'message' => count($this->selectedRows) . ' cotización(es) eliminada(s) exitosamente.']);
        }

        $this->selectedRows = [];
        $this->allSelected = false;
    }

    #[Layout('components.layouts.app')]
    #[Title('Cotizador Rápido')]
    public function render()
    {
        $likeOperator = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite' ? 'like' : 'ilike';

        $budgets = QuickBudget::query()
            ->with(['client', 'creator'])
            ->when($this->search, function ($q) use ($likeOperator) {
                $q->where(function ($query) use ($likeOperator) {
                    $query->where('title', $likeOperator, "%{$this->search}%")
                          ->orWhere('description', $likeOperator, "%{$this->search}%")
                          ->orWhereHas('client', function ($cq) use ($likeOperator) {
                              $cq->where('name', $likeOperator, "%{$this->search}%");
                          });
                });
            })
            ->when($this->periodFilter, function ($q) {
                $now = now();
                if ($this->periodFilter === 'custom') {
                    if ($this->dateFrom) {
                        $q->where('created_at', '>=', $this->dateFrom . ' 00:00:00');
                    }
                    if ($this->dateTo) {
                        $q->where('created_at', '<=', $this->dateTo . ' 23:59:59');
                    }
                    return $q;
                }
                return match ($this->periodFilter) {
                    'this_month' => $q->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year),
                    'last_month' => $q->whereMonth('created_at', $now->subMonth()->month)->whereYear('created_at', $now->subMonth()->year),
                    'this_quarter' => $q->whereBetween('created_at', [$now->copy()->firstOfQuarter(), $now->copy()->lastOfQuarter()]),
                    'this_year' => $q->whereYear('created_at', $now->year),
                    default => $q
                };
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->userFilter, fn ($q) => $q->where('user_id', $this->userFilter))
            ->withCount('items')
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        $users = \App\Models\User::orderBy('name')->get();
        $statuses = QuickBudgetStatus::toArray();

        return view('livewire.quick-budgets.quick-budget-index', compact('budgets', 'users', 'statuses'));
    }
}
