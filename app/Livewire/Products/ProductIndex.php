<?php

namespace App\Livewire\Products;

use App\Livewire\Concerns\EnforcesPermissions;
use App\Livewire\Concerns\WithSorting;
use App\DTOs\ProductDTO;
use App\Models\Category;
use App\Models\Measure;
use App\Models\Product;
use App\Models\RequisitionItem;
use App\Repositories\ProductRepository;
use App\Models\Supplier;
use App\Services\DataNormalizerService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProductIndex extends Component
{
    use EnforcesPermissions, WithPagination, WithSorting;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $categoryFilter = '';

    #[Url(history: true)]
    public string $measureFilter = '';

    public array $selectedRows = [];

    public bool $allSelected = false;

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
        $this->selectedRows = [];
        $this->allSelected = false;
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
        $this->selectedRows = [];
        $this->allSelected = false;
    }

    public function mount(): void
    {
        $this->sortField = 'canonical_name';
        $this->sortDirection = 'asc';
    }

    #[On('edit-product')]
    public function handleEditProduct(int $id): void
    {
        $this->openEditModal($id);
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
        if ($this->denyUnless('productos.crear', 'No tienes permiso para guardar productos.')) {
            return;
        }

        $this->validate([
            'canonicalName' => 'required|min:2|max:255|unique:products,canonical_name,'.$this->editingId,
            'measureId' => 'required|exists:measures,id',
            'description' => 'nullable|max:500',
            'categoryId' => 'required|exists:categories,id',
        ]);

        // Verificar duplicado por normalized_name (evita "Cemento Gris" vs "CEMENTO GRIS")
        $normalizer = app(DataNormalizerService::class);
        $normalizedName = $normalizer->normalizeText($this->canonicalName);

        $existingByNormalized = Product::where('normalized_name', $normalizedName)
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->first();

        if ($existingByNormalized) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'Ya existe un producto similar: "'.$existingByNormalized->canonical_name.'". Usa el catálogo existente.']);
            return;
        }

        $dto = new ProductDTO(
            canonical_name: $this->canonicalName,
            measure_id: $this->measureId,
            description: $this->description ?: null,
            category_id: $this->categoryId,
            id: $this->editingId,
        );

        app(ProductRepository::class)->save($dto);

        if ($this->editingId) {
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Producto actualizado correctamente.']);
        } else {
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Producto registrado en el catálogo maestro.']);
        }

        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function deleteProduct(int $productId): void
    {
        if ($this->denyUnless('productos.eliminar', 'No tienes permiso para eliminar productos.')) {
            return;
        }

        $product = Product::findOrFail($productId);

        if (RequisitionItem::where('product_id', $productId)->exists()) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No se puede eliminar: el producto está siendo utilizado en una requisición.']);
            return;
        }

        app(ProductRepository::class)->delete($productId);
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Producto eliminado del catálogo.']);
        $this->selectedRows = array_diff($this->selectedRows, [$productId]);
    }

    public function toggleAll($productIds): void
    {
        if ($this->allSelected) {
            $this->selectedRows = array_merge($this->selectedRows, $productIds);
            $this->selectedRows = array_unique($this->selectedRows);
        } else {
            $this->selectedRows = array_diff($this->selectedRows, $productIds);
        }
    }

    public function bulkDelete(): void
    {
        if ($this->denyUnless('productos.eliminar', 'No tienes permiso para eliminar productos.')) {
            return;
        }

        if (empty($this->selectedRows)) {
            return;
        }

        // Obtener productos en uso
        $usedProducts = RequisitionItem::whereIn('product_id', $this->selectedRows)->pluck('product_id')->toArray();

        $productsToDelete = array_diff($this->selectedRows, $usedProducts);

        if (count($usedProducts) > 0) {
            $this->dispatch('toast', ['icon' => 'warning', 'message' => 'Algunos productos no pudieron ser eliminados porque están en uso.']);
        }

        if (count($productsToDelete) > 0) {
            app(ProductRepository::class)->bulkDelete($productsToDelete);
            $this->dispatch('toast', ['icon' => 'success', 'message' => count($productsToDelete) . ' producto(s) eliminado(s) exitosamente.']);
        }

        $this->selectedRows = [];
        $this->allSelected = false;
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
            ->when($this->search, fn ($q) => $q->where('canonical_name', 'like', "%{$this->search}%"))
            ->when($this->categoryFilter, fn ($q) => $q->where('category_id', $this->categoryFilter))
            ->when($this->measureFilter, fn ($q) => $q->where('measure_id', $this->measureFilter))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        $suppliers = Supplier::orderBy('trade_name')->get();
        $measures = Measure::pluck('name', 'id')->toArray();
        $categories = Category::orderBy('name')->pluck('name', 'id')->toArray();

        return view('livewire.products.product-index', compact(
            'products',
            'suppliers',
            'measures',
            'categories'
        ));
    }
}
