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
            $this->isOpen = false;
            return;
        }

        $searchTerm = '%' . $this->query . '%';
        $this->results = [];

        // Buscar requisiciones (por folio o nombre de proyecto)
        $requisitions = Requisition::with('project')
            ->where('number', 'like', $searchTerm)
            ->orWhereHas('project', function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm);
            })
            ->limit(5)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->number,
                'subtitle' => $item->project?->name ?? 'Sin proyecto',
                'url' => url('/requisiciones'),
                'type' => 'requisition',
                'typeLabel' => 'Requisición',
                'icon' => 'clipboard-list',
            ])
            ->toArray();

        // Buscar proveedores
        $suppliers = Supplier::where('trade_name', 'like', $searchTerm)
            ->orWhere('legal_name', 'like', $searchTerm)
            ->orWhere('rfc', 'like', $searchTerm)
            ->limit(5)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->trade_name,
                'subtitle' => $item->legal_name ?: $item->rfc,
                'url' => url('/proveedores'),
                'type' => 'supplier',
                'typeLabel' => 'Proveedor',
                'icon' => 'truck',
            ])
            ->toArray();

        // Buscar proyectos
        $projects = Project::where('name', 'like', $searchTerm)
            ->orWhere('description', 'like', $searchTerm)
            ->where('status', 'activo')
            ->limit(5)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->name,
                'subtitle' => 'Presupuesto: $' . number_format($item->total_budget, 2),
                'url' => url('/proyectos'),
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
            ->limit(5)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->name,
                'subtitle' => $item->category?->name ?? 'Sin categoría',
                'url' => url('/productos'),
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

        $hasResults = !empty($requisitions) || !empty($suppliers) || !empty($projects) || !empty($products);
        $this->isOpen = $hasResults;
    }

    public function clear(): void
    {
        $this->query = '';
        $this->results = [];
        $this->isOpen = false;
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
