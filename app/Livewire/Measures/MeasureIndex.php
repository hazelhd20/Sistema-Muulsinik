<?php

namespace App\Livewire\Measures;

use App\Livewire\Concerns\EnforcesPermissions;
use App\Livewire\Concerns\WithFilters;
use App\Livewire\Concerns\WithSorting;
use App\DTOs\MeasureDTO;
use App\Models\Measure;
use App\Models\Product;
use App\Models\RequisitionItem;
use App\Repositories\MeasureRepository;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

use App\Livewire\Concerns\WithPerPagePagination;

class MeasureIndex extends Component
{
    use EnforcesPermissions, WithFilters, WithPagination, WithSorting, WithPerPagePagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $usageFilter = '';

    #[Url(history: true)]
    public string $trashedFilter = '';

    public array $usageOptions = [
        'in_use' => 'En uso (Con productos o requisiciones)',
        'empty' => 'Sin uso (Vacías / Huérfanas)',
    ];

    public array $trashedOptions = [
        'trashed' => 'En Papelera / Eliminadas',
        'all' => 'Todas (Activas y Eliminadas)',
    ];

    public string $name = '';

    public string $abbreviation = '';

    public ?int $editingId = null;

    public bool $showCreateModal = false;

    public array $selectedRows = [];

    public bool $allSelected = false;

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:measures,name,' . $this->editingId,
            'abbreviation' => 'nullable|string|max:50',
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
        $this->selectedRows = [];
        $this->allSelected = false;
    }

    public function mount(): void
    {
        if (! auth()->user()?->hasPermission('catalogos.ver') && ! auth()->user()?->hasPermission('*')) {
            abort(403, 'No tienes permiso para acceder al catálogo de unidades de medida.');
        }

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

        $this->name = app(\App\Services\DataNormalizerService::class)->normalizeTitleCase($this->name);

        $this->validate();

        $dto = new MeasureDTO(
            name: $this->name,
            abbreviation: $this->abbreviation ?: null,
            id: $this->editingId,
        );

        app(MeasureRepository::class)->save($dto);

        if ($this->editingId) {
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Medida actualizada exitosamente.']);
        } else {
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Medida creada exitosamente.']);
        }

        $this->reset(['name', 'abbreviation', 'editingId', 'showCreateModal']);
    }

    public function restore(int $id): void
    {
        if ($this->denyUnless('catalogos.editar', 'No tienes permiso para modificar catálogos.')) {
            return;
        }

        $measure = Measure::onlyTrashed()->findOrFail($id);
        $measure->restore();
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Medida restaurada exitosamente.']);
        $this->selectedRows = array_diff($this->selectedRows, [$id]);
    }

    public function forceDelete(int $id): void
    {
        if ($this->denyUnless('catalogos.eliminar', 'No tienes permiso para eliminar permanentemente catálogos.')) {
            return;
        }

        $measure = Measure::withTrashed()->findOrFail($id);
        $measure->forceDelete();
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Medida eliminada permanentemente.']);
        $this->selectedRows = array_diff($this->selectedRows, [$id]);
    }

    public function delete(int $id): void
    {
        if ($this->denyUnless('catalogos.editar', 'No tienes permiso para modificar catálogos.')) {
            return;
        }

        $measure = Measure::withTrashed()->findOrFail($id);

        if ($measure->trashed()) {
            $this->forceDelete($id);
            return;
        }

        $isUsed = RequisitionItem::where('measure_id', $measure->id)->exists() ||
                  Product::where('measure_id', $measure->id)->exists();

        if ($isUsed) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No se puede eliminar: la medida está en uso por productos o requisiciones.']);

            return;
        }

        app(MeasureRepository::class)->delete($id);
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

        if ($this->trashedFilter === 'trashed') {
            if ($this->denyUnless('catalogos.eliminar', 'No tienes permiso para eliminar permanentemente catálogos.')) {
                return;
            }
            Measure::onlyTrashed()->whereIn('id', $this->selectedRows)->forceDelete();
            $this->dispatch('toast', ['icon' => 'success', 'message' => count($this->selectedRows) . ' medida(s) eliminada(s) permanentemente.']);
            $this->selectedRows = [];
            $this->allSelected = false;
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

        app(MeasureRepository::class)->bulkDelete($measuresToDelete);

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
        $measures = Measure::query()
            ->when($this->trashedFilter === 'trashed', fn ($q) => $q->onlyTrashed())
            ->when($this->trashedFilter === 'all', fn ($q) => $q->withTrashed())
            ->when($this->search, function ($q) {
                $q->where(fn ($sub) => $sub->where('name', 'ilike', '%'.$this->search.'%')
                  ->orWhere('abbreviation', 'ilike', '%'.$this->search.'%'));
            })
            ->when($this->usageFilter === 'in_use', function ($q) {
                $q->where(fn ($sub) => $sub->has('products')->orHas('requisitionItems'));
            })
            ->when($this->usageFilter === 'empty', function ($q) {
                $q->doesntHave('products')->doesntHave('requisitionItems');
            })
            ->withCount('products')
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.measures.measure-index', compact('measures'));
    }
}
