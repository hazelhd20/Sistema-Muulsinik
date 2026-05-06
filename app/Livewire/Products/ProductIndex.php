<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Models\Supplier;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class ProductIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $categoryFilter = '';
    public bool $showCreateModal = false;

    // Campos del producto
    public string $canonicalName = '';
    public string $measureId = '';
    public string $description = '';
    public string $category = '';

    protected array $categories = [
        'acero' => 'Acero / Herrería',
        'agregados' => 'Agregados',
        'cemento' => 'Cemento / Concreto',
        'electrico' => 'Material Eléctrico',
        'herramientas' => 'Herramientas',
        'hidraulico' => 'Material Hidráulico',
        'madera' => 'Madera',
        'pintura' => 'Pintura',
        'plomeria' => 'Plomería',
        'seguridad' => 'Equipo de Seguridad',
        'otros' => 'Otros',
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function createProduct(): void
    {
        $this->validate([
            'canonicalName' => 'required|min:2|max:255|unique:products,canonical_name',
            'measureId' => 'required|exists:measures,id',
            'description' => 'nullable|max:500',
            'category' => 'required',
        ]);

        Product::create([
            'canonical_name' => $this->canonicalName,
            'measure_id' => $this->measureId,
            'description' => $this->description ?: null,
            'category' => $this->category,
        ]);

        $this->showCreateModal = false;
        $this->resetForm();
        session()->flash('success', 'Producto registrado en el catálogo maestro.');
    }

    public function deleteProduct(int $productId): void
    {
        $product = Product::findOrFail($productId);
        
        if (\App\Models\RequisitionItem::where('product_id', $productId)->exists()) {
            session()->flash('error', 'No se puede eliminar: el producto está siendo utilizado en una requisición.');
            return;
        }

        $product->delete();
        session()->flash('success', 'Producto eliminado del catálogo.');
    }

    private function resetForm(): void
    {
        $this->canonicalName = '';
        $this->measureId = '';
        $this->description = '';
        $this->category = '';
    }

    #[Layout('components.layouts.app')]
    #[Title('Productos')]
    public function render()
    {
        $products = Product::query()
            ->when($this->search, fn($q) => $q->where('canonical_name', 'like', "%{$this->search}%"))
            ->when($this->categoryFilter, fn($q) => $q->where('category', $this->categoryFilter))
            ->orderBy('canonical_name')
            ->paginate(15);

        $suppliers = \App\Models\Supplier::orderBy('trade_name')->get();
        $measures = \App\Models\Measure::orderBy('name')->pluck('name', 'id')->toArray();
        $categories = $this->categories;

        return view('livewire.products.product-index', compact(
            'products',
            'suppliers',
            'measures',
            'categories'
        ));
    }
}
