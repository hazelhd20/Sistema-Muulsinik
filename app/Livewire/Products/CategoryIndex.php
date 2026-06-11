<?php

namespace App\Livewire\Products;

use App\Livewire\Concerns\EnforcesPermissions;
use App\Livewire\Concerns\WithSorting;
use App\Models\Category;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class CategoryIndex extends Component
{
    use EnforcesPermissions, WithPagination, WithSorting;

    public string $search = '';

    public string $name = '';

    public ?int $editingId = null;

    public bool $showCreateModal = false;

    public array $selectedRows = [];

    public bool $allSelected = false;

    protected $rules = [
        'name' => 'required|string|max:255|unique:categories,name',
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
        $this->reset(['name', 'editingId']);
        $this->showCreateModal = true;
    }

    public function openEditModal(int $id): void
    {
        $category = Category::findOrFail($id);
        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->showCreateModal = true;
    }

    public function save(): void
    {
        if ($this->denyUnless('catalogos.editar', 'No tienes permiso para modificar catálogos.')) {
            return;
        }

        $this->validate([
            'name' => 'required|string|max:255|unique:categories,name,'.$this->editingId,
        ]);

        if ($this->editingId) {
            Category::findOrFail($this->editingId)->update([
                'name' => $this->name,
            ]);
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Categoría actualizada exitosamente.']);
        } else {
            Category::create([
                'name' => $this->name,
            ]);
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Categoría creada exitosamente.']);
        }

        $this->reset(['name', 'editingId', 'showCreateModal']);
    }

    public function delete(int $id): void
    {
        if ($this->denyUnless('catalogos.editar', 'No tienes permiso para modificar catálogos.')) {
            return;
        }

        $category = Category::findOrFail($id);

        $isUsed = Product::where('category_id', $category->id)->exists();

        if ($isUsed) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No se puede eliminar: la categoría está en uso por productos.']);

            return;
        }

        $category->delete();
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Categoría eliminada exitosamente.']);
        
        $this->selectedRows = array_diff($this->selectedRows, [$id]);
    }

    public function toggleAll($categoryIds): void
    {
        if ($this->allSelected) {
            $this->selectedRows = array_merge($this->selectedRows, $categoryIds);
            $this->selectedRows = array_unique($this->selectedRows);
        } else {
            $this->selectedRows = array_diff($this->selectedRows, $categoryIds);
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

        // Only delete categories that don't have products
        $categoriesToDelete = Category::whereIn('id', $this->selectedRows)
            ->whereDoesntHave('products')
            ->pluck('id');

        if ($categoriesToDelete->count() < count($this->selectedRows)) {
            $this->dispatch('toast', ['icon' => 'warning', 'message' => 'Algunas categorías no pudieron ser eliminadas porque están en uso.']);
        }

        Category::whereIn('id', $categoriesToDelete)->delete();

        if ($categoriesToDelete->count() > 0) {
            $this->dispatch('toast', ['icon' => 'success', 'message' => $categoriesToDelete->count() . ' categoría(s) eliminada(s) exitosamente.']);
        }

        $this->selectedRows = [];
        $this->allSelected = false;
    }

    #[Layout('components.layouts.app')]
    #[Title('Catálogo de Categorías')]
    public function render()
    {
        $categories = Category::where('name', 'like', '%'.$this->search.'%')
            ->withCount('products')
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('livewire.products.category-index', compact('categories'));
    }
}
