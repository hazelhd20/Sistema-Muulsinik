<?php

namespace App\Livewire\Measures;

use App\Livewire\Concerns\EnforcesPermissions;
use App\Livewire\Concerns\WithSorting;
use App\Models\Measure;
use App\Models\Product;
use App\Models\RequisitionItem;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class MeasureIndex extends Component
{
    use EnforcesPermissions, WithPagination, WithSorting;

    public string $search = '';

    public string $name = '';

    public string $abbreviation = '';

    public ?int $editingId = null;

    public bool $showCreateModal = false;

    public array $selectedRows = [];

    public bool $allSelected = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'abbreviation' => 'nullable|string|max:50',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
        $this->selectedRows = [];
        $this->allSelected = false;
    }

    public function mount(): void
    {
        $this->sortField = 'name';
        $this->sortDirection = 'asc';
    }

    public function openCreateModal(): void
    {
        $this->reset(['name', 'abbreviation', 'editingId']);
        $this->showCreateModal = true;
    }

    public function openEditModal(int $id): void
    {
        $measure = Measure::findOrFail($id);
        $this->editingId = $measure->id;
        $this->name = $measure->name;
        $this->abbreviation = $measure->abbreviation ?? '';
        $this->showCreateModal = true;
    }

    public function save(): void
    {
        if ($this->denyUnless('catalogos.editar', 'No tienes permiso para modificar catálogos.')) {
            return;
        }

        $this->validate();

        if ($this->editingId) {
            Measure::findOrFail($this->editingId)->update([
                'name' => $this->name,
                'abbreviation' => $this->abbreviation ?: null,
            ]);
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Medida actualizada exitosamente.']);
        } else {
            Measure::create([
                'name' => $this->name,
                'abbreviation' => $this->abbreviation ?: null,
            ]);
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Medida creada exitosamente.']);
        }

        $this->reset(['name', 'abbreviation', 'editingId', 'showCreateModal']);
    }

    public function delete(int $id): void
    {
        if ($this->denyUnless('catalogos.editar', 'No tienes permiso para modificar catálogos.')) {
            return;
        }

        $measure = Measure::findOrFail($id);

        $isUsed = RequisitionItem::where('measure_id', $measure->id)->exists() ||
                  Product::where('measure_id', $measure->id)->exists();

        if ($isUsed) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No se puede eliminar: la medida está en uso por productos o requisiciones.']);

            return;
        }

        $measure->delete();
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Medida eliminada exitosamente.']);
        
        $this->selectedRows = array_diff($this->selectedRows, [$id]);
    }

    public function toggleAll($measureIds): void
    {
        if ($this->allSelected) {
            $this->selectedRows = array_merge($this->selectedRows, $measureIds);
            $this->selectedRows = array_unique($this->selectedRows);
        } else {
            $this->selectedRows = array_diff($this->selectedRows, $measureIds);
        }
    }

    public function bulkDelete(): void
    {
        if ($this->denyUnless('catalogos.editar', 'No tienes permiso para modificar catálogos.')) {
            return;
        }

        if (empty($this->selectedRows)) {
            return;
        }

        // Obtener medidas en uso por productos o requisiciones
        $usedInProducts = Product::whereIn('measure_id', $this->selectedRows)->pluck('measure_id')->toArray();
        $usedInRequisitions = RequisitionItem::whereIn('measure_id', $this->selectedRows)->pluck('measure_id')->toArray();
        
        $usedMeasures = array_unique(array_merge($usedInProducts, $usedInRequisitions));

        $measuresToDelete = array_diff($this->selectedRows, $usedMeasures);

        if (count($usedMeasures) > 0) {
            $this->dispatch('toast', ['icon' => 'warning', 'message' => 'Algunas medidas no pudieron ser eliminadas porque están en uso.']);
        }

        Measure::whereIn('id', $measuresToDelete)->delete();

        if (count($measuresToDelete) > 0) {
            $this->dispatch('toast', ['icon' => 'success', 'message' => count($measuresToDelete) . ' medida(s) eliminada(s) exitosamente.']);
        }

        $this->selectedRows = [];
        $this->allSelected = false;
    }

    #[Layout('components.layouts.app')]
    #[Title('Catálogo de Medidas')]
    public function render()
    {
        $measures = Measure::where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                  ->orWhere('abbreviation', 'like', '%'.$this->search.'%');
            })
            ->withCount('products')
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('livewire.measures.measure-index', compact('measures'));
    }
}
