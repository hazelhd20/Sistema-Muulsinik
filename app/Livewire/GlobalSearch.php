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

    public function updatedQuery(): void
    {
        $this->search();
    }

    public function search(): void
    {
        if (strlen($this->query) < 2) {
            $this->results = [];

            return;
        }

        $searchTerm = '%'.$this->query.'%';
        $this->results = [];

        // Buscar requisiciones (por folio o nombre de proyecto)
        $requisitions = Requisition::with('project')
            ->where('number', 'like', $searchTerm)
            ->orWhereHas('project', function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm);
            })
            ->limit(self::RESULTS_LIMIT)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->number,
                'subtitle' => $item->project?->name ?? 'Sin proyecto',
                'url' => route('requisiciones.show', $item->id),
                'type' => 'requisition',
                'typeLabel' => 'Requisición',
                'icon' => 'clipboard-list',
            ])
            ->toArray();

        // Buscar proveedores
        $suppliers = Supplier::where('trade_name', 'like', $searchTerm)
            ->orWhere('legal_name', 'like', $searchTerm)
            ->orWhere('rfc', 'like', $searchTerm)
            ->limit(self::RESULTS_LIMIT)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->trade_name,
                'subtitle' => $item->legal_name ?: $item->rfc,
                'url' => route('proveedores.index', ['search' => $item->trade_name]),
                'type' => 'supplier',
                'typeLabel' => 'Proveedor',
                'icon' => 'truck',
            ])
            ->toArray();

        // Buscar proyectos (solo activos)
        $projects = Project::where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('description', 'like', $searchTerm);
            })
            ->where('status', 'activo')
            ->limit(self::RESULTS_LIMIT)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->name,
                'subtitle' => 'Presupuesto: $'.number_format(floatval($item->total_budget), 2),
                'url' => route('proyectos.show', $item->id),
                'type' => 'project',
                'typeLabel' => 'Proyecto',
                'icon' => 'hard-hat',
            ])
            ->toArray();

        // Buscar productos
        $products = Product::with('category')
            ->where('name', 'like', $searchTerm)
            ->orWhere('sku', 'like', $searchTerm)
            ->orWhereHas('category', function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm);
            })
            ->limit(self::RESULTS_LIMIT)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->name,
                'subtitle' => $item->category?->name ?? 'Sin categoría',
                'url' => route('productos.index', ['search' => $item->name]),
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
