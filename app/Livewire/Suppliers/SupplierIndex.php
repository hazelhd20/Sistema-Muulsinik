<?php

namespace App\Livewire\Suppliers;

use App\Models\Supplier;
use App\Models\Vendor;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showCreateModal = false;
    public bool $showVendorsModal = false;
    public ?int $viewingSupplierId = null;

    // Campos proveedor
    public string $tradeName = '';
    public string $legalName = '';
    public string $rfc = '';
    public string $category = '';
    public string $notes = '';

    // Campos vendedor
    public bool $showAddVendor = false;
    public string $vendorName = '';
    public string $vendorPhone = '';
    public string $vendorEmail = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function createSupplier(): void
    {
        $this->validate([
            'tradeName' => 'required|min:2|max:255',
            'legalName' => 'nullable|max:255',
            'rfc' => 'nullable|max:13',
            'category' => 'nullable|max:255',
            'notes' => 'nullable|max:1000',
        ]);

        Supplier::create([
            'trade_name' => $this->tradeName,
            'legal_name' => $this->legalName ?: null,
            'rfc' => $this->rfc ?: null,
            'category' => $this->category ?: null,
            'notes' => $this->notes ?: null,
        ]);

        $this->showCreateModal = false;
        $this->resetForm();
        session()->flash('success', 'Proveedor registrado correctamente.');
    }

    public function viewVendors(int $supplierId): void
    {
        $this->viewingSupplierId = $supplierId;
        $this->showVendorsModal = true;
        $this->showAddVendor = false;
        $this->vendorName = '';
        $this->vendorPhone = '';
        $this->vendorEmail = '';
    }

    public function addVendor(): void
    {
        $this->validate([
            'vendorName' => 'required|min:2|max:255',
            'vendorPhone' => 'nullable|max:20',
            'vendorEmail' => 'nullable|email|max:255',
        ]);

        Vendor::create([
            'supplier_id' => $this->viewingSupplierId,
            'name' => $this->vendorName,
            'phone' => $this->vendorPhone ?: null,
            'email' => $this->vendorEmail ?: null,
        ]);

        $this->vendorName = '';
        $this->vendorPhone = '';
        $this->vendorEmail = '';
        $this->showAddVendor = false;
        session()->flash('vendor_success', 'Vendedor agregado.');
    }

    public function deleteVendor(int $vendorId): void
    {
        Vendor::findOrFail($vendorId)->delete();
    }

    public function deleteSupplier(int $supplierId): void
    {
        $supplier = Supplier::findOrFail($supplierId);

        $isUsed = \App\Models\RequisitionItem::where('supplier_id', $supplierId)->exists() ||
                  \App\Models\Quotation::where('supplier_id', $supplierId)->exists();

        if ($isUsed) {
            session()->flash('error', 'No se puede eliminar: el proveedor está siendo utilizado en requisiciones o cotizaciones.');
            return;
        }

        $supplier->delete();
        session()->flash('success', 'Proveedor eliminado.');
    }

    private function resetForm(): void
    {
        $this->tradeName = '';
        $this->legalName = '';
        $this->rfc = '';
        $this->category = '';
        $this->notes = '';
    }

    #[Layout('components.layouts.app')]
    #[Title('Proveedores')]
    public function render()
    {
        $suppliers = Supplier::withCount('vendors')
            ->when($this->search, fn($q) => $q->where('trade_name', 'like', "%{$this->search}%")
                ->orWhere('rfc', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(12);

        $viewingSupplier = $this->viewingSupplierId
            ? Supplier::with('vendors')->find($this->viewingSupplierId)
            : null;

        return view('livewire.suppliers.supplier-index', compact('suppliers', 'viewingSupplier'));
    }
}
