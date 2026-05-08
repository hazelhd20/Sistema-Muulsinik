<?php

namespace App\Livewire\Products;

use App\Livewire\Concerns\EnforcesPermissions;
use App\Models\Category;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class CategoryIndex extends Component
{
    use WithPagination, EnforcesPermissions;

    public string $search = '';

    public string $name = '';
    public ?int $editingId = null;
    public bool $showCreateModal = false;

    protected $rules = [
        'name' => 'required|string|max:255|unique:categories,name',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
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
        if ($this->denyUnless('catalogos.editar', 'No tienes permiso para modificar catálogos.')) return;

        $this->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $this->editingId,
        ]);

        if ($this->editingId) {
            Category::findOrFail($this->editingId)->update([
                'name' => $this->name,
            ]);
            session()->flash('success', 'Categoría actualizada exitosamente.');
        } else {
            Category::create([
                'name' => $this->name,
            ]);
            session()->flash('success', 'Categoría creada exitosamente.');
        }

        $this->reset(['name', 'editingId', 'showCreateModal']);
    }

    public function delete(int $id): void
    {
        if ($this->denyUnless('catalogos.editar', 'No tienes permiso para modificar catálogos.')) return;

        $category = Category::findOrFail($id);

        $isUsed = \App\Models\Product::where('category_id', $category->id)->exists();

        if ($isUsed) {
            session()->flash('error', 'No se puede eliminar: la categoría está en uso por productos.');
            return;
        }

        $category->delete();
        session()->flash('success', 'Categoría eliminada exitosamente.');
    }

    #[Layout('components.layouts.app')]
    #[Title('Catálogo de Categorías')]
    public function render()
    {
        $categories = Category::where('name', 'like', '%' . $this->search . '%')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.products.category-index', compact('categories'));
    }
}
