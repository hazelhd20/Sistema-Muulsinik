<?php

namespace App\Livewire\Products;

use App\Livewire\Concerns\EnforcesPermissions;
use App\Livewire\Concerns\WithFilters;
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
use Illuminate\Validation\Rule;

use App\Livewire\Concerns\WithPerPagePagination;

class ProductIndex extends Component
{
    use EnforcesPermissions, WithFilters, WithPagination, WithSorting, WithPerPagePagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $categoryFilter = '';

    #[Url(history: true)]
    public string $measureFilter = '';

    #[Url(history: true)]
    public string $trashedFilter = '';

    #[Url(history: true)]
    public string $typeFilter = '';

    public array $selectedRows = [];

    public bool $allSelected = false;

    public bool $showCreateModal = false;

    public string $canonicalName = '';

    public string $itemType = 'material';

    public string $measureId = '';

    public string $description = '';

    public string $categoryId = '';

    public ?int $editingId = null;

    public function mount(): void
    {
        if (! auth()->user()?->hasPermission('productos.ver') && ! auth()->user()?->hasPermission('*')) {
            abort(403, 'No tienes permiso para acceder al catálogo de productos.');
        }

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
        $this->itemType = $product->item_type ?? 'material';
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
            'canonicalName' => ['required', 'min:2', 'max:255', Rule::unique('products', 'canonical_name')->ignore($this->editingId)],
            'itemType' => 'required|in:material,labor,service',
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
            item_type: $this->itemType,
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

    public function restore(int $productId): void
    {
        if ($this->denyUnless('productos.editar', 'No tienes permiso para restaurar productos.')) {
            return;
        }

        $product = Product::onlyTrashed()->findOrFail($productId);
        $product->restore();

        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Producto restaurado exitosamente.']);
    }

    public function forceDelete(int $productId): void
    {
        if ($this->denyUnless('productos.eliminar', 'No tienes permiso para eliminar productos.')) {
            return;
        }

        $product = Product::withTrashed()->findOrFail($productId);

        if (RequisitionItem::where('product_id', $productId)->exists()) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No se puede eliminar definitivamente: el producto está siendo utilizado en una requisición.']);
            return;
        }

        $product->forceDelete();
        $this->selectedRows = array_diff($this->selectedRows, [$productId]);
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Producto eliminado definitivamente.']);
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

        if ($this->trashedFilter === 'trashed') {
            $productsToDelete = Product::onlyTrashed()->whereIn('id', $this->selectedRows)->get();
            $deletedCount = 0;
            $inUseCount = 0;

            foreach ($productsToDelete as $product) {
                if (RequisitionItem::where('product_id', $product->id)->exists()) {
                    $inUseCount++;
                    continue;
                }
                $product->forceDelete();
                $deletedCount++;
            }

            if ($inUseCount > 0) {
                $this->dispatch('toast', ['icon' => 'warning', 'message' => "{$inUseCount} producto(s) no se pudieron eliminar porque están en uso."]);
            }
            if ($deletedCount > 0) {
                $this->dispatch('toast', ['icon' => 'success', 'message' => "{$deletedCount} producto(s) eliminado(s) definitivamente."]);
            }
        } else {
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
        }

        $this->selectedRows = [];
        $this->allSelected = false;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->canonicalName = '';
        $this->itemType = 'material';
        $this->measureId = '';
        $this->description = '';
        $this->categoryId = '';
    }

    #[Layout('components.layouts.app')]
    #[Title('Productos')]
    public function render()
    {
        $products = Product::query()
            ->when($this->trashedFilter === 'trashed', fn ($q) => $q->onlyTrashed())
            ->when($this->trashedFilter === 'all', fn ($q) => $q->withTrashed())
            ->with(['category', 'measure'])
            ->when($this->search, fn ($q) => $q->where('canonical_name', 'ilike', "%{$this->search}%"))
            ->when($this->categoryFilter, fn ($q) => $q->where('category_id', $this->categoryFilter))
            ->when($this->measureFilter, fn ($q) => $q->where('measure_id', $this->measureFilter))
            ->when($this->typeFilter, fn ($q) => $q->where('item_type', $this->typeFilter))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $suppliers = \Illuminate\Support\Facades\Cache::remember('catalog_suppliers', now()->addHours(12), function() {
            return Supplier::orderBy('trade_name')->get();
        });
        
        $measures = \Illuminate\Support\Facades\Cache::remember('catalog_measures', now()->addHours(12), function() {
            return Measure::pluck('name', 'id')->toArray();
        });
        
        $categories = \Illuminate\Support\Facades\Cache::remember('catalog_categories', now()->addHours(12), function() {
            return Category::orderBy('name')->pluck('name', 'id')->toArray();
        });

        $itemTypes = [
            'material' => 'Material',
            'labor' => 'Mano de obra',
            'service' => 'Servicio',
        ];

        $trashedOptions = [
            'trashed' => 'En papelera',
            'all' => 'Todos (activos y eliminados)',
        ];

        $typeOptions = [
            'material' => 'Material',
            'labor' => 'Mano de obra',
            'service' => 'Servicio',
        ];

        return view('livewire.products.product-index', compact(
            'products',
            'suppliers',
            'measures',
            'categories',
            'itemTypes',
            'trashedOptions',
            'typeOptions'
        ));
    }
}

