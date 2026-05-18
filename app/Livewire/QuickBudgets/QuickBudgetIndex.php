<?php

namespace App\Livewire\QuickBudgets;

use App\Models\QuickBudget;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class QuickBudgetIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function deleteBudget(int $id): void
    {
        QuickBudget::findOrFail($id)->delete();
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Cotización eliminada.']);
    }

    #[Layout('components.layouts.app')]
    #[Title('Cotizador Rápido')]
    public function render()
    {
        $budgets = QuickBudget::query()
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%")
                ->orWhere('client', 'like', "%{$this->search}%"))
            ->withCount('items')
            ->latest()
            ->paginate(15);

        return view('livewire.quick-budgets.quick-budget-index', compact('budgets'));
    }
}
