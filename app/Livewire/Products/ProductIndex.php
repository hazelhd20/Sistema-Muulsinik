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
    public string $unit = '';
    public string $description = '';
    public string $category = '';

    protected array $units = [
        'pza' => 'Pieza',
        'kg' => 'Kilogramo',
        'm' => 'Metro',
        'm2' => 'Metro cuadrado',
        'm3' => 'Metro cúbico',
        'lt' => 'Litro',
        'bulto' => 'Bulto',
        'rollo' => 'Rollo',
        'ton' => 'Tonelada',
        'cubo' => 'Cubo',
        'tramo' => 'Tramo',
        'servicio' => 'Servicio',
    ];

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
            'unit' => 'required',
            'description' => 'nullable|max:500',
            'category' => 'required',
        ]);

        Product::create([
            'canonical_name' => $this->canonicalName,
            'unit' => $this->unit,
            'description' => $this->description ?: null,
            'category' => $this->category,
        ]);

        $this->showCreateModal = false;
        $this->resetForm();
        session()->flash('success', 'Producto registrado en el catálogo maestro.');
    }

    public function deleteProduct(int $productId): void
    {
        Product::findOrFail($productId)->delete();
        session()->flash('success', 'Producto eliminado del catálogo.');
    }

    private function resetForm(): void
    {
        $this->canonicalName = '';
        $this->unit = '';
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

        $suppliers = Supplier::orderBy('trade_name')->get();
        $units = $this->units;
        $categories = $this->categories;

        return view('livewire.products.product-index', compact(
            'products',
            'suppliers',
            'units',
            'categories'
        ));
    }
}
