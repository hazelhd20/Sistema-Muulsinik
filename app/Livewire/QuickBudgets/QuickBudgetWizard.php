<?php

namespace App\Livewire\QuickBudgets;

use App\Models\Product;
use App\Models\QuickBudget;
use App\Models\QuickBudgetItem;
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

        $this->searchResults = Product::with('measure', 'category')
            ->where('canonical_name', 'like', "%{$this->searchQuery}%")
            ->take(10)
            ->get()
            ->map(function ($product) {
                // Find the latest purchase price for this product
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
                    'measure_id' => $product->measure_id,
                    'measure_abbr' => $product->measure ? $product->measure->abbreviation : '—',
                    'last_price' => $lastPrice ? (float) $lastPrice : 0,
                ];
            })
            ->toArray();
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

    public function save()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'client' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.concept' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () {
            $budget = QuickBudget::updateOrCreate(
                ['id' => $this->budgetId],
                [
                    'title' => $this->title,
                    'description' => $this->description,
                    'client' => $this->client,
                    'subtotal' => $this->subtotal,
                    'tax_amount' => 0, // Simplified for quick budgets
                    'total' => $this->subtotal,
                    'margin_percent' => $this->marginPercent,
                    'grand_total' => $this->grand_total,
                    'created_by' => auth()->id(),
                ]
            );

            // Delete old items if updating
            if ($this->budgetId) {
                QuickBudgetItem::where('quick_budget_id', $this->budgetId)->delete();
            }

            foreach ($this->items as $item) {
                QuickBudgetItem::create([
                    'quick_budget_id' => $budget->id,
                    'product_id' => $item['product_id'],
                    'concept' => $item['concept'],
                    'measure_id' => $item['measure_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['line_total'],
                ]);
            }
        });

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
