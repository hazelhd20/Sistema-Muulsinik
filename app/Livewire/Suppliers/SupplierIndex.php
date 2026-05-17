<?php

namespace App\Livewire\Suppliers;

use App\Livewire\Concerns\EnforcesPermissions;
use App\Models\Supplier;
use App\Models\Vendor;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierIndex extends Component
{
    use WithPagination, EnforcesPermissions;

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

    public ?int $editingSupplierId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function openEditSupplierModal(int $supplierId): void
    {
        $supplier = Supplier::findOrFail($supplierId);
        $this->editingSupplierId = $supplier->id;
        $this->tradeName = $supplier->trade_name;
        $this->legalName = $supplier->legal_name ?? '';
        $this->rfc = $supplier->rfc ?? '';
        $this->category = $supplier->category ?? '';
        $this->notes = $supplier->notes ?? '';
        
        $this->showCreateModal = true;
    }

    public function saveSupplier(): void
    {
        if ($this->denyUnless('proveedores.crear', 'No tienes permiso para guardar proveedores.')) return;

        $this->validate([
            'tradeName' => 'required|min:2|max:255',
            'legalName' => 'nullable|max:255',
            'rfc' => 'nullable|max:13',
            'category' => 'nullable|max:255',
            'notes' => 'nullable|max:1000',
        ]);

        // Verificar duplicado por normalized_name (evita "CEMEX S.A." vs "Cemex SA")
        $normalizer = app(\App\Services\DataNormalizerService::class);
        $normalizedName = $normalizer->normalizeSupplierName($this->tradeName);

        $existingByNormalized = Supplier::where('normalized_name', $normalizedName)
            ->when($this->editingSupplierId, fn($q) => $q->where('id', '!=', $this->editingSupplierId))
            ->first();

        if ($existingByNormalized) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'Ya existe un proveedor similar: "' . $existingByNormalized->trade_name . '". Verifica el catálogo.']);
            return;
        }

        if ($this->editingSupplierId) {
            Supplier::findOrFail($this->editingSupplierId)->update([
                'trade_name' => $this->tradeName,
                'legal_name' => $this->legalName ?: null,
                'rfc' => $this->rfc ?: null,
                'category' => $this->category ?: null,
                'notes' => $this->notes ?: null,
            ]);
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Proveedor actualizado correctamente.']);
        } else {
            Supplier::create([
                'trade_name' => $this->tradeName,
                'legal_name' => $this->legalName ?: null,
                'rfc' => $this->rfc ?: null,
                'category' => $this->category ?: null,
                'notes' => $this->notes ?: null,
            ]);
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Proveedor registrado correctamente.']);
        }

        $this->showCreateModal = false;
        $this->resetForm();
    }

    public ?int $editingVendorId = null;

    public function viewVendors(int $supplierId): void
    {
        $this->viewingSupplierId = $supplierId;
        $this->showVendorsModal = true;
        $this->showAddVendor = false;
        $this->editingVendorId = null;
        $this->vendorName = '';
        $this->vendorPhone = '';
        $this->vendorEmail = '';
    }

    public function openEditVendor(int $vendorId): void
    {
        $vendor = Vendor::findOrFail($vendorId);
        $this->editingVendorId = $vendor->id;
        $this->vendorName = $vendor->name;
        $this->vendorPhone = $vendor->phone ?? '';
        $this->vendorEmail = $vendor->email ?? '';
        $this->showAddVendor = true;
    }

    public function saveVendor(): void
    {
        if ($this->denyUnless('proveedores.editar', 'No tienes permiso para guardar vendedores.')) return;

        $this->validate([
            'vendorName' => 'required|min:2|max:255',
            'vendorPhone' => 'nullable|max:20',
            'vendorEmail' => 'nullable|email|max:255',
        ]);

        if ($this->editingVendorId) {
            Vendor::findOrFail($this->editingVendorId)->update([
                'name' => $this->vendorName,
                'phone' => $this->vendorPhone ?: null,
                'email' => $this->vendorEmail ?: null,
            ]);
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Vendedor actualizado.']);
        } else {
            Vendor::create([
                'supplier_id' => $this->viewingSupplierId,
                'name' => $this->vendorName,
                'phone' => $this->vendorPhone ?: null,
                'email' => $this->vendorEmail ?: null,
            ]);
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Vendedor agregado.']);
        }

        $this->vendorName = '';
        $this->vendorPhone = '';
        $this->vendorEmail = '';
        $this->showAddVendor = false;
        $this->editingVendorId = null;
    }

    public function deleteVendor(int $vendorId): void
    {
        if ($this->denyUnless('proveedores.editar', 'No tienes permiso para eliminar vendedores.')) return;

        Vendor::findOrFail($vendorId)->delete();
    }

    public function deleteSupplier(int $supplierId): void
    {
        if ($this->denyUnless('proveedores.eliminar', 'No tienes permiso para eliminar proveedores.')) return;

        $supplier = Supplier::findOrFail($supplierId);

        $isUsed = \App\Models\RequisitionItem::where('supplier_id', $supplierId)->exists() ||
                  \App\Models\Quotation::where('supplier_id', $supplierId)->exists();

        if ($isUsed) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No se puede eliminar: el proveedor está siendo utilizado en requisiciones o cotizaciones.']);
            return;
        }

        $supplier->delete();
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Proveedor eliminado.']);
    }

    private function resetForm(): void
    {
        $this->editingSupplierId = null;
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
