<?php

namespace App\Livewire\Requisitions;

use App\Models\Category;
use App\Models\Measure;
use App\Models\Product;
use App\Models\Project;
use App\Models\Vendor;
use App\Services\RequisitionItemResolverService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class ManualRequisition extends Component
{
    public ManualRequisitionForm $form;

    // Search for products (pattern from QuickBudgetWizard)
    public string $searchQuery = '';

    public array $searchResults = [];

    public function mount()
    {
        $this->form->date = now()->format('Y-m-d');
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
        if (! $product) {
            return;
        }

        $this->form->addProduct($product);

        $this->searchQuery = '';
        $this->searchResults = [];
    }

    public function addManualItem(): void
    {
        $this->form->addManualItem();
    }

    public function removeItem(int $index): void
    {
        $this->form->removeItem($index);
    }

    public function createRequisition(): void
    {
        $error = $this->form->validateForm();

        if ($error) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => $error]);
            return;
        }

        $resolver = app(RequisitionItemResolverService::class);
        $resolver->createRequisitionWithItems(
            [
                'project_id' => $this->form->projectId,
                'vendor_id' => $this->form->vendorId ?: null,
                'annotations' => $this->form->annotations,
                'status' => 'borrador',
                'created_by' => auth()->id(),
                'date' => $this->form->date,
            ],
            $this->form->items
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
        $measures = Measure::getOptions();

        // Totales calculados en el componente — la vista solo los consume
        $subtotal = collect($this->form->items)->sum(
            fn ($item) => ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0)
        );
        $iva = round($subtotal * 0.16, 2);
        $totals = [
            'subtotal' => $subtotal,
            'iva' => $iva,
            'total' => round($subtotal + $iva, 2),
        ];

        return view('livewire.requisitions.manual-requisition', compact(
            'projects',
            'vendors',
            'categories',
            'measures',
            'totals'
        ));
    }
}
