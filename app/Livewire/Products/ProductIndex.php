<?php

namespace App\Livewire\Products;

use App\Livewire\Concerns\EnforcesPermissions;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Category;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class ProductIndex extends Component
{
    use WithPagination, EnforcesPermissions;

    public string $search = '';
    public string $categoryFilter = '';
    public bool $showCreateModal = false;

    // Campos del producto
    public string $canonicalName = '';
    public string $measureId = '';
    public string $description = '';
    public string $categoryId = '';

    public ?int $editingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function openEditModal(int $productId): void
    {
        $product = Product::findOrFail($productId);
        $this->editingId = $product->id;
        $this->canonicalName = $product->canonical_name;
        $this->measureId = $product->measure_id;
        $this->description = $product->description ?? '';
        $this->categoryId = $product->category_id ?? '';

        $this->showCreateModal = true;
    }

    public function saveProduct(): void
    {
        if ($this->denyUnless('productos.crear', 'No tienes permiso para guardar productos.')) return;

        $this->validate([
            'canonicalName' => 'required|min:2|max:255|unique:products,canonical_name,' . $this->editingId,
            'measureId' => 'required|exists:measures,id',
            'description' => 'nullable|max:500',
            'categoryId' => 'required|exists:categories,id',
        ]);

        // Verificar duplicado por normalized_name (evita "Cemento Gris" vs "CEMENTO GRIS")
        $normalizer = app(\App\Services\DataNormalizerService::class);
        $normalizedName = $normalizer->normalizeText($this->canonicalName);

        $existingByNormalized = Product::where('normalized_name', $normalizedName)
            ->when($this->editingId, fn($q) => $q->where('id', '!=', $this->editingId))
            ->first();

        if ($existingByNormalized) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'Ya existe un producto similar: "' . $existingByNormalized->canonical_name . '". Usa el catálogo existente.']);
            return;
        }

        if ($this->editingId) {
            Product::findOrFail($this->editingId)->update([
                'canonical_name' => $this->canonicalName,
                'measure_id' => $this->measureId,
                'description' => $this->description ?: null,
                'category_id' => $this->categoryId,
            ]);
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Producto actualizado correctamente.']);
        } else {
            Product::create([
                'canonical_name' => $this->canonicalName,
                'measure_id' => $this->measureId,
                'description' => $this->description ?: null,
                'category_id' => $this->categoryId,
            ]);
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Producto registrado en el catálogo maestro.']);
        }

        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function deleteProduct(int $productId): void
    {
        if ($this->denyUnless('productos.eliminar', 'No tienes permiso para eliminar productos.')) return;

        $product = Product::findOrFail($productId);

        if (\App\Models\RequisitionItem::where('product_id', $productId)->exists()) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No se puede eliminar: el producto está siendo utilizado en una requisición.']);
            return;
        }

        $product->delete();
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Producto eliminado del catálogo.']);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->canonicalName = '';
        $this->measureId = '';
        $this->description = '';
        $this->categoryId = '';
    }

    #[Layout('components.layouts.app')]
    #[Title('Productos')]
    public function render()
    {
        $products = Product::query()
            ->with(['category', 'measure'])
            ->when($this->search, fn($q) => $q->where('canonical_name', 'like', "%{$this->search}%"))
            ->when($this->categoryFilter, fn($q) => $q->where('category_id', $this->categoryFilter))
            ->orderBy('canonical_name')
            ->paginate(15);

        $suppliers = \App\Models\Supplier::orderBy('trade_name')->get();
        $measures = \App\Models\Measure::orderBy('name')->pluck('name', 'id')->toArray();
        $categories = Category::orderBy('name')->pluck('name', 'id')->toArray();

        return view('livewire.products.product-index', compact(
            'products',
            'suppliers',
            'measures',
            'categories'
        ));
    }
}
