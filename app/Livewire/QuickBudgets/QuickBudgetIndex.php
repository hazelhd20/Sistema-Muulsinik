<?php

namespace App\Livewire\QuickBudgets;

use App\Livewire\Concerns\WithSorting;
use App\Models\QuickBudget;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class QuickBudgetIndex extends Component
{
    use WithPagination, WithSorting;

    public string $search = '';

    public string $periodFilter = '';

    public array $selectedRows = [];

    public bool $allSelected = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->selectedRows = [];
        $this->allSelected = false;
    }

    public function deleteBudget(int $id): void
    {
        QuickBudget::findOrFail($id)->delete();
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

    public function bulkDelete(): void
    {
        if (empty($this->selectedRows)) {
            return;
        }

        QuickBudget::whereIn('id', $this->selectedRows)->delete();

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
        $budgets = QuickBudget::query()
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%")
                ->orWhere('client', 'like', "%{$this->search}%")
                ->orWhere('folio', 'like', "%{$this->search}%"))
            ->when($this->periodFilter, function ($q) {
                $now = now();
                match ($this->periodFilter) {
                    'this_month' => $q->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year),
                    'last_month' => $q->whereMonth('created_at', $now->subMonth()->month)->whereYear('created_at', $now->subMonth()->year),
                    'this_quarter' => $q->whereRaw('QUARTER(created_at) = ?', [$now->quarter])->whereYear('created_at', $now->year),
                    'this_year' => $q->whereYear('created_at', $now->year),
                    default => $q
                };
            })
            ->withCount('items')
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('livewire.quick-budgets.quick-budget-index', compact('budgets'));
    }
}
