<?php

namespace App\Livewire\Requisitions;

use App\Models\Category;
use App\Models\Measure;
use App\Models\Product;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\Vendor;
use App\Services\RequisitionItemResolverService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class ManualRequisition extends Component
{
    public $reqProjectId = '';
    public $reqVendorId = '';
    public string $reqAnnotations = '';
    public string $reqDate = '';

    // Ítems temporales para la nueva requisición
    public array $items = [];

    // Search for products (pattern from QuickBudgetWizard)
    public string $searchQuery = '';
    public array $searchResults = [];

    public function mount()
    {
        $this->reqDate = now()->format('Y-m-d');
    }

    public function updatedSearchQuery()
    {
        if (strlen($this->searchQuery) < 2) {
            $this->searchResults = [];
            return;
        }

        $categories = Category::all()->keyBy('id');
        $measures = Measure::all()->keyBy('id');

        $this->searchResults = Product::with('measure', 'category')
            ->where('canonical_name', 'like', "%{$this->searchQuery}%")
            ->take(10)
            ->get()
            ->map(function ($product) {
                $lastPrice = DB::table('requisition_items')
                    ->join('requisitions', 'requisitions.id', '=', 'requisition_items.requisition_id')
                    ->where('requisition_items.product_id', $product->id)
                    ->where('requisitions.status', 'aprobada')
                    ->orderByDesc('requisitions.created_at')
                    ->value('unit_price');

                return [
                    'id' => $product->id,
                    'name' => $product->canonical_name,
                    'category' => $product->category ? $product->category->name : 'Sin categoría',
                    'category_id' => $product->category_id,
                    'measure_abbr' => $product->measure ? $product->measure->abbreviation : '—',
                    'unit' => $product->measure ? ($product->measure->abbreviation ?: $product->measure->name) : 'pza',
                    'last_price' => $lastPrice ? (float) $lastPrice : 0,
                ];
            })
            ->toArray();
    }

    public function addProduct(int $index): void
    {
        $product = $this->searchResults[$index] ?? null;
        if (!$product) {
            return;
        }

        $this->items[] = [
            'name' => $product['name'],
            'quantity' => 1,
            'unit' => $product['unit'],
            'unit_price' => $product['last_price'],
            'category_id' => $product['category_id'],
        ];

        $this->searchQuery = '';
        $this->searchResults = [];
    }

    public function addManualItem(): void
    {
        $this->items[] = [
            'name' => '',
            'quantity' => 1,
            'unit' => 'pza',
            'unit_price' => 0,
            'category_id' => null,
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function createRequisition(): void
    {
        $this->validate([
            'reqProjectId' => 'required|exists:projects,id',
            'reqVendorId' => 'nullable|exists:vendors,id',
            'reqAnnotations' => 'nullable|max:500',
            'reqDate' => 'required|date',
        ]);

        if (empty($this->items)) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'Agrega al menos un producto a la requisición.']);
            return;
        }

        // Validate items have names
        foreach ($this->items as $i => $item) {
            if (empty(trim($item['name'] ?? ''))) {
                $this->dispatch('toast', ['icon' => 'error', 'message' => 'El producto en la fila ' . ($i + 1) . ' no tiene nombre.']);
                return;
            }
            if (($item['quantity'] ?? 0) <= 0) {
                $this->dispatch('toast', ['icon' => 'error', 'message' => 'La cantidad en la fila ' . ($i + 1) . ' debe ser mayor a 0.']);
                return;
            }
        }

        $resolver = app(RequisitionItemResolverService::class);
        $resolver->createRequisitionWithItems(
            [
                'project_id' => $this->reqProjectId,
                'vendor_id' => $this->reqVendorId ?: null,
                'annotations' => $this->reqAnnotations,
                'status' => 'borrador',
                'created_by' => auth()->id(),
                'date' => $this->reqDate,
            ],
            $this->items
        );

        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Requisición creada como borrador.']);
        $this->redirect(route('requisiciones.index'), navigate: true);
    }

    #[Layout('components.layouts.app')]
    #[Title('Nueva Requisición Manual')]
    public function render()
    {
        $projects = Project::where('status', 'activo')->orderBy('name')->get();
        $vendors = Vendor::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $measures = Measure::orderBy('name')->get();

        return view('livewire.requisitions.manual-requisition', compact(
            'projects',
            'vendors',
            'categories',
            'measures'
        ));
    }
}
