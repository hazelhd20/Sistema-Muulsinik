<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Project;
use App\Models\Requisition;
use App\Models\Supplier;
use Livewire\Attributes\On;
use Livewire\Component;

class GlobalSearch extends Component
{
    private const RESULTS_LIMIT = 5;

    public string $query = '';

    public bool $isOpen = false;

    public array $results = [];

    public array $categories = [
        'requisitions' => 'Requisiciones',
        'projects' => 'Proyectos',
        'suppliers' => 'Proveedores',
        'products' => 'Productos',
    ];

    public array $colorMap = [
        'requisitions' => 'primary',
        'projects' => 'warning',
        'suppliers' => 'success',
        'products' => 'info',
    ];

    public function updatedQuery(): void
    {
        $this->search();
    }

    protected function highlight(?string $text): string
    {
        if (empty($text) || empty($this->query)) {
            return e((string)$text);
        }

        $escapedQuery = preg_quote($this->query, '/');
        // Usamos \b o simplemente ignoramos el case.
        return preg_replace('/(' . $escapedQuery . ')/i', '<strong class="text-primary-600 font-bold bg-primary-50 rounded px-0.5">$1</strong>', e($text));
    }

    public function search(): void
    {
        if (strlen($this->query) < 2) {
            $this->results = [];

            return;
        }

        try {
            $this->results = [];

            // Buscar requisiciones
            $requisitions = Requisition::search($this->query)
                ->take(self::RESULTS_LIMIT)
                ->query(fn ($query) => $query->with('project'))
                ->get()
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'title' => $this->highlight($item->number),
                    'subtitle' => $this->highlight($item->project?->name ?? 'Sin proyecto'),
                    'url' => route('requisiciones.show', $item->id),
                    'type' => 'requisition',
                    'typeLabel' => 'Requisición',
                    'icon' => 'clipboard-list',
                ])
                ->toArray();

            // Buscar proveedores
            $suppliers = Supplier::search($this->query)
                ->take(self::RESULTS_LIMIT)
                ->get()
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'title' => $this->highlight($item->trade_name),
                    'subtitle' => $this->highlight($item->legal_name ?: $item->rfc),
                    'url' => route('proveedores.index', ['search' => $item->trade_name]),
                    'type' => 'supplier',
                    'typeLabel' => 'Proveedor',
                    'icon' => 'truck',
                ])
                ->toArray();

            // Buscar proyectos (solo activos)
            $projects = Project::search($this->query)
                ->take(self::RESULTS_LIMIT * 2) 
                ->get()
                ->filter(fn($p) => $p->status === 'activo')
                ->take(self::RESULTS_LIMIT)
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'title' => $this->highlight($item->name),
                    'subtitle' => 'Presupuesto: $'.number_format(floatval($item->total_budget), 2),
                    'url' => route('proyectos.show', $item->id),
                    'type' => 'project',
                    'typeLabel' => 'Proyecto',
                    'icon' => 'hard-hat',
                ])
                ->values()
                ->toArray();

            // Buscar productos
            $products = Product::search($this->query)
                ->take(self::RESULTS_LIMIT)
                ->query(fn ($query) => $query->with('category'))
                ->get()
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'title' => $this->highlight($item->canonical_name),
                    'subtitle' => $this->highlight($item->category?->name ?? 'Sin categoría'),
                    'url' => route('productos.index', ['search' => $item->canonical_name]),
                    'type' => 'product',
                    'typeLabel' => 'Producto',
                    'icon' => 'package',
                ])
                ->toArray();

            $this->results = [
                'requisitions' => $requisitions,
                'suppliers' => $suppliers,
                'projects' => $projects,
                'products' => $products,
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error en búsqueda global: ' . $e->getMessage());
            $this->results = [];
        }
    }

    public function clear(): void
    {
        $this->query = '';
        $this->results = [];
    }

    #[On('open-global-search')]
    public function open(): void
    {
        $this->isOpen = true;
        $this->dispatch('focus-global-search');
    }

    public function render()
    {
        return view('livewire.global-search');
    }
}
