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

    public bool $includeTax = false;

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
            $this->includeTax = (float) $budget->tax_amount > 0;

            foreach ($budget->items as $item) {
                $this->items[] = [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'concept' => $item->concept,
                    'measure_id' => $item->measure_id,
                    'measure_abbr' => $item->measure ? $item->measure->abbreviation : '—',
                    'quantity' => (float) $item->quantity,
                    'unit_cost' => (float) $item->unit_cost,
                    'margin_percent' => (float) $item->margin_percent,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                ];
            }
        } else {
            // Defaults
            $this->marginPercent = 20; // 20% by default
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
            ->get(['requisition_items.product_id', 'requisition_items.unit_price', 'requisitions.created_at'])
            ->groupBy('product_id')
            ->map(function ($items) {
                // Top 3 unique prices
                return $items->unique('unit_price')->take(3)->values();
            });

        $this->searchResults = $products->map(function ($product) use ($latestPrices) {
            $pricesHistory = $latestPrices->get($product->id) ?? collect();
            $lastPrice = $pricesHistory->first() ? $pricesHistory->first()->unit_price : 0;
            // Max price could be used as safe default
            $maxPrice = $pricesHistory->max('unit_price') ?? 0;
            
            // We use max price to be safe on budget
            $safeCost = max($lastPrice, $maxPrice);

            return [
                'id' => $product->id,
                'name' => $product->canonical_name,
                'category' => $product->category ? $product->category->name : 'Sin categoría',
                'measure_id' => $product->measure_id,
                'measure_abbr' => $product->measure ? $product->measure->abbreviation : '—',
                'last_price' => (float) $safeCost,
                'history' => $pricesHistory->map(fn($p) => ['price' => (float) $p->unit_price, 'date' => \Carbon\Carbon::parse($p->created_at)->format('d/m/Y')])->toArray(),
            ];
        })->toArray();
    }

    public function addProduct($index)
    {
        $product = $this->searchResults[$index] ?? null;
        if (! $product) {
            return;
        }

        $cost = $product['last_price'];
        $salePrice = round($cost * (1 + ($this->marginPercent / 100)), 2);

        $this->items[] = [
            'id' => null,
            'product_id' => $product['id'],
            'concept' => $product['name'],
            'measure_id' => $product['measure_id'],
            'measure_abbr' => $product['measure_abbr'],
            'quantity' => 1,
            'unit_cost' => $cost,
            'margin_percent' => $this->marginPercent,
            'unit_price' => $salePrice,
            'line_total' => $salePrice,
            'history' => $product['history'] ?? [],
        ];

        $this->searchQuery = '';
        $this->searchResults = [];
    }

    public function addManualItem()
    {
        $this->items[] = [
            'id' => null,
            'product_id' => null,
            'concept' => 'Nuevo concepto',
            'measure_id' => null, // Will use text or select
            'measure_abbr' => 'SRV',
            'quantity' => 1,
            'unit_cost' => 0,
            'margin_percent' => $this->marginPercent,
            'unit_price' => 0,
            'line_total' => 0,
            'history' => [],
        ];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updatedMarginPercent()
    {
        foreach ($this->items as &$item) {
            $item['margin_percent'] = (float) $this->marginPercent;
            $cost = (float) ($item['unit_cost'] ?? 0);
            $item['unit_price'] = round($cost * (1 + ($item['margin_percent'] / 100)), 2);
            $qty = (float) ($item['quantity'] ?? 0);
            $item['line_total'] = round($qty * $item['unit_price'], 2);
        }
    }

    public function updated($property, $value)
    {
        if (str_starts_with($property, 'items.')) {
            $parts = explode('.', $property);
            $index = $parts[1];
            $field = $parts[2];

            if ($field === 'unit_price') {
                // User explicitly changed sale price -> adjust margin
                $cost = (float) ($this->items[$index]['unit_cost'] ?? 0);
                $sale = (float) $value;
                if ($cost > 0) {
                    $this->items[$index]['margin_percent'] = round((($sale / $cost) - 1) * 100, 2);
                } else {
                    $this->items[$index]['margin_percent'] = 100;
                }
            } elseif ($field === 'unit_cost' || $field === 'margin_percent') {
                // Cost or Margin changed -> recalculate sale price
                $cost = (float) ($this->items[$index]['unit_cost'] ?? 0);
                $margin = (float) ($this->items[$index]['margin_percent'] ?? 0);
                $this->items[$index]['unit_price'] = round($cost * (1 + ($margin / 100)), 2);
            }
            
            // Recalculate line total if related fields changed
            if (in_array($field, ['quantity', 'unit_price', 'unit_cost', 'margin_percent'])) {
                $qty = (float) ($this->items[$index]['quantity'] ?? 0);
                $salePrice = (float) ($this->items[$index]['unit_price'] ?? 0);
                $this->items[$index]['line_total'] = round($qty * $salePrice, 2);
            }
            
            // If measure changed for manual item, find the abbr
            if ($field === 'measure_id' && empty($this->items[$index]['product_id'])) {
                $measure = Measure::find($value);
                $this->items[$index]['measure_abbr'] = $measure ? ($measure->abbreviation ?: $measure->name) : 'SRV';
            }
        }
    }

    public function getCostSubtotalProperty(): float
    {
        return collect($this->items)->sum(fn($item) => ($item['quantity'] ?? 0) * ($item['unit_cost'] ?? 0));
    }

    public function getSubtotalProperty(): float
    {
        return collect($this->items)->sum('line_total');
    }

    public function getTaxAmountProperty(): float
    {
        return $this->includeTax ? round($this->subtotal * 0.16, 2) : 0;
    }

    public function getGrandTotalProperty(): float
    {
        return round($this->subtotal + $this->tax_amount, 2);
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
            'includeTax' => $this->includeTax,
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
        return view('livewire.quick-budgets.quick-budget-wizard', [
            'measures' => Measure::getOptions()
        ]);
    }
}
