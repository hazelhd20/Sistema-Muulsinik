<?php

namespace App\Livewire\Requisitions;

use App\Models\Category;
use App\Models\Measure;
use App\Models\Product;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\Vendor;
use App\Services\RequisitionItemResolverService;
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
    public string $itemName = '';
    public string $itemQuantity = '';
    public string $itemUnit = 'pza';
    public string $itemPrice = '';
    public $itemSupplierId = '';
    public $itemCategoryId = '';

    public function mount()
    {
        $this->reqDate = now()->format('Y-m-d');
    }

    public function addItem(): void
    {
        $this->validate([
            'itemName' => 'required|min:2',
            'itemQuantity' => 'required|numeric|min:0.01',
            'itemUnit' => 'required',
            'itemPrice' => 'required|numeric|min:0',
        ]);

        $this->items[] = [
            'name' => $this->itemName,
            'quantity' => (float) $this->itemQuantity,
            'unit' => $this->itemUnit,
            'unit_price' => (float) $this->itemPrice,
            'supplier_id' => $this->itemSupplierId ?: null,
            'category_id' => $this->itemCategoryId ?: null,
        ];

        $this->itemName = '';
        $this->itemQuantity = '';
        $this->itemPrice = '';
        $this->itemSupplierId = '';
        $this->itemCategoryId = '';
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

        // Crear requisición con todos sus items usando el servicio
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
        $products = Product::orderBy('canonical_name')->get();

        return view('livewire.requisitions.manual-requisition', compact(
            'projects',
            'vendors',
            'categories',
            'measures',
            'products'
        ));
    }
}
