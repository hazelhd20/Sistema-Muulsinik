<?php

namespace App\Livewire\QuickBudgets;

use App\DTOs\QuickBudgetDTO;
use App\Models\Category;
use App\Models\Measure;
use App\Models\Product;
use App\Models\QuickBudget;
use App\Models\QuickBudgetItem;
use App\Repositories\QuickBudgetRepository;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class QuickBudgetWizard extends Component
{
    public ?int $budgetId = null;

    // Header
    public string $title = '';

    public string $description = '';

    public string $client = '';

    public float $marginPercent = 0;

    // Items list
    public array $items = [];

    // Search for products
    public string $searchQuery = '';

    public array $searchResults = [];

    public function mount(?int $id = null)
    {
        if ($id) {
            $budget = QuickBudget::with('items.product', 'items.measure')->findOrFail($id);
            $this->budgetId = $budget->id;
            $this->title = $budget->title;
            $this->description = $budget->description ?? '';
            $this->client = $budget->client ?? '';
            $this->marginPercent = (float) $budget->margin_percent;

            foreach ($budget->items as $item) {
                $this->items[] = [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'concept' => $item->concept,
                    'measure_id' => $item->measure_id,
                    'measure_abbr' => $item->measure ? $item->measure->abbreviation : '—',
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                ];
            }
        }
    }

    public function updatedSearchQuery()
    {
        if (strlen($this->searchQuery) < 2) {
            $this->searchResults = [];

            return;
        }

        $products = Product::with('measure', 'category')
            ->where('canonical_name', 'ilike', "%{$this->searchQuery}%")
            ->take(10)
            ->get();

        $productIds = $products->pluck('id');

        $latestPrices = DB::table('requisition_items')
            ->join('requisitions', 'requisitions.id', '=', 'requisition_items.requisition_id')
            ->whereIn('requisition_items.product_id', $productIds)
            ->where('requisitions.status', 'aprobada')
            ->orderByDesc('requisitions.created_at')
            ->get(['requisition_items.product_id', 'requisition_items.unit_price'])
            ->groupBy('product_id')
            ->map(fn($items) => $items->first()->unit_price);

        $this->searchResults = $products->map(function ($product) use ($latestPrices) {
            $lastPrice = $latestPrices->get($product->id);

            return [
                'id' => $product->id,
                'name' => $product->canonical_name,
                'category' => $product->category ? $product->category->name : 'Sin categoría',
                'measure_id' => $product->measure_id,
                'measure_abbr' => $product->measure ? $product->measure->abbreviation : '—',
                'last_price' => $lastPrice ? (float) $lastPrice : 0,
            ];
        })->toArray();
    }

    public function addProduct($index)
    {
        $product = $this->searchResults[$index] ?? null;
        if (! $product) {
            return;
        }

        $this->items[] = [
            'id' => null,
            'product_id' => $product['id'],
            'concept' => $product['name'],
            'measure_id' => $product['measure_id'],
            'measure_abbr' => $product['measure_abbr'],
            'quantity' => 1,
            'unit_price' => $product['last_price'],
            'line_total' => $product['last_price'],
        ];

        $this->searchQuery = '';
        $this->searchResults = [];
        $this->recalculateTotals();
    }

    public function addManualItem()
    {
        $this->items[] = [
            'id' => null,
            'product_id' => null,
            'concept' => 'Nuevo concepto',
            'measure_id' => null,
            'measure_abbr' => 'SRV',
            'quantity' => 1,
            'unit_price' => 0,
            'line_total' => 0,
        ];
        $this->recalculateTotals();
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->recalculateTotals();
    }

    public function updatedItems()
    {
        $this->recalculateTotals();
    }

    public function updatedMarginPercent()
    {
        $this->recalculateTotals();
    }

    private function recalculateTotals()
    {
        foreach ($this->items as &$item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            $item['line_total'] = round($qty * $price, 2);
        }
    }

    public function getSubtotalProperty(): float
    {
        return collect($this->items)->sum('line_total');
    }

    public function getGrandTotalProperty(): float
    {
        $subtotal = $this->subtotal;
        $margin = $subtotal * ($this->marginPercent / 100);

        return round($subtotal + $margin, 2);
    }

    public function save(QuickBudgetRepository $repository)
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'client' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.concept' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $dto = QuickBudgetDTO::fromArray([
            'title' => $this->title,
            'description' => $this->description,
            'client' => $this->client,
            'marginPercent' => $this->marginPercent,
            'items' => $this->items,
        ], auth()->id());

        $budget = $repository->save($this->budgetId, $dto);

        $this->budgetId = $budget->id;
        session()->flash('success', 'Cotización guardada exitosamente.');

        return $this->redirect(route('cotizador.index'), navigate: true);
    }

    #[Layout('components.layouts.app')]
    #[Title('Cotizador - Editar')]
    public function render()
    {
        return view('livewire.quick-budgets.quick-budget-wizard');
    }
}
