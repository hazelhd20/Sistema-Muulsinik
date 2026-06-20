<?php

namespace App\Livewire\Suppliers;

use App\Livewire\Concerns\EnforcesPermissions;
use App\Livewire\Concerns\WithFilters;
use App\Livewire\Concerns\WithSorting;
use App\DTOs\SupplierDTO;
use App\DTOs\VendorDTO;
use App\Models\Quotation;
use App\Models\RequisitionItem;
use App\Models\Supplier;
use App\Models\Vendor;
use App\Repositories\SupplierRepository;
use App\Repositories\VendorRepository;
use App\Services\DataNormalizerService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierIndex extends Component
{
    use EnforcesPermissions, WithFilters, WithPagination, WithSorting;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $categoryFilter = '';

    public array $selectedRows = [];

    public bool $allSelected = false;

    public bool $showCreateModal = false;

    public bool $showVendorsModal = false;

    public ?int $viewingSupplierId = null;

    // Campos proveedor
    public string $tradeName = '';

    public string $legalName = '';

    public string $rfc = '';

    public string $category = '';

    public string $notes = '';

    public bool $active = true;

    // Campos vendedor
    public bool $showAddVendor = false;

    public string $vendorName = '';

    public string $vendorPhone = '';

    public string $vendorEmail = '';

    public ?int $editingSupplierId = null;



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
        $this->active = $supplier->active ?? true;

        $this->showCreateModal = true;
    }

    public function saveSupplier(): void
    {
        if ($this->denyUnless('proveedores.crear', 'No tienes permiso para guardar proveedores.')) {
            return;
        }

        $this->validate([
            'tradeName' => 'required|min:2|max:255',
            'legalName' => 'nullable|max:255',
            'rfc' => 'nullable|max:13',
            'category' => 'nullable|max:255',
            'notes' => 'nullable|max:1000',
        ]);

        // Verificar duplicado por normalized_name (evita "CEMEX S.A." vs "Cemex SA")
        $normalizer = app(DataNormalizerService::class);
        $normalizedName = $normalizer->normalizeSupplierName($this->tradeName);

        $existingByNormalized = Supplier::where('normalized_name', $normalizedName)
            ->when($this->editingSupplierId, fn ($q) => $q->where('id', '!=', $this->editingSupplierId))
            ->first();

        if ($existingByNormalized) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'Ya existe un proveedor similar: "'.$existingByNormalized->trade_name.'". Verifica el catálogo.']);

            return;
        }

        $dto = new SupplierDTO(
            trade_name: $this->tradeName,
            legal_name: $this->legalName ?: null,
            rfc: $this->rfc ?: null,
            category: $this->category ?: null,
            notes: $this->notes ?: null,
            active: $this->active,
            id: $this->editingSupplierId,
        );

        app(SupplierRepository::class)->save($dto);

        if ($this->editingSupplierId) {
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Proveedor actualizado correctamente.']);
        } else {
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
        if ($this->denyUnless('proveedores.editar', 'No tienes permiso para guardar vendedores.')) {
            return;
        }

        $this->validate([
            'vendorName' => 'required|min:2|max:255',
            'vendorPhone' => 'nullable|max:20',
            'vendorEmail' => 'nullable|email|max:255',
        ]);

        $dto = new VendorDTO(
            supplier_id: $this->viewingSupplierId,
            name: $this->vendorName,
            phone: $this->vendorPhone ?: null,
            email: $this->vendorEmail ?: null,
            id: $this->editingVendorId,
        );

        app(VendorRepository::class)->save($dto);

        if ($this->editingVendorId) {
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Vendedor actualizado.']);
        } else {
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
        if ($this->denyUnless('proveedores.editar', 'No tienes permiso para eliminar vendedores.')) {
            return;
        }

        app(VendorRepository::class)->delete($vendorId);
    }

    public function deleteSupplier(int $supplierId): void
    {
        if ($this->denyUnless('proveedores.eliminar', 'No tienes permiso para eliminar proveedores.')) {
            return;
        }

        $supplier = Supplier::findOrFail($supplierId);

        $isUsed = RequisitionItem::where('supplier_id', $supplierId)->exists() ||
            Quotation::where('supplier_id', $supplierId)->exists();

        if ($isUsed) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No se puede eliminar: el proveedor está siendo utilizado en requisiciones o cotizaciones.']);

            return;
        }

        app(SupplierRepository::class)->delete($supplierId);
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Proveedor eliminado.']);
        $this->selectedRows = array_diff($this->selectedRows, [$supplierId]);
    }

    public function toggleAll($supplierIds): void
    {
        if ($this->allSelected) {
            $this->selectedRows = array_merge($this->selectedRows, $supplierIds);
            $this->selectedRows = array_unique($this->selectedRows);
        } else {
            $this->selectedRows = array_diff($this->selectedRows, $supplierIds);
        }
    }

    public function bulkDelete(): void
    {
        if ($this->denyUnless('proveedores.eliminar', 'No tienes permiso para eliminar proveedores.')) {
            return;
        }

        if (empty($this->selectedRows)) {
            return;
        }

        // Obtener proveedores en uso
        $usedInRequisitions = RequisitionItem::whereIn('supplier_id', $this->selectedRows)->pluck('supplier_id')->toArray();
        $usedInQuotations = Quotation::whereIn('supplier_id', $this->selectedRows)->pluck('supplier_id')->toArray();

        $usedSuppliers = array_unique(array_merge($usedInRequisitions, $usedInQuotations));
        $suppliersToDelete = array_diff($this->selectedRows, $usedSuppliers);

        if (count($usedSuppliers) > 0) {
            $this->dispatch('toast', ['icon' => 'warning', 'message' => 'Algunos proveedores no pudieron ser eliminados porque están en uso.']);
        }

        if (count($suppliersToDelete) > 0) {
            foreach ($suppliersToDelete as $sid) {
                app(SupplierRepository::class)->delete($sid);
            }
            $this->dispatch('toast', ['icon' => 'success', 'message' => count($suppliersToDelete) . ' proveedor(es) eliminado(s) exitosamente.']);
        }

        $this->selectedRows = [];
        $this->allSelected = false;
    }

    public function toggleActiveSupplier(int $id): void
    {
        if ($this->denyUnless('proveedores.editar', 'No tienes permiso para editar proveedores.')) {
            return;
        }

        $supplier = app(SupplierRepository::class)->toggleActive($id);

        $status = $supplier->active ? 'activado' : 'desactivado';
        $this->dispatch('toast', ['icon' => 'success', 'message' => "Proveedor {$status} correctamente."]);
    }

    private function resetForm(): void
    {
        $this->editingSupplierId = null;
        $this->tradeName = '';
        $this->legalName = '';
        $this->rfc = '';
        $this->category = '';
        $this->notes = '';
        $this->active = true;
    }

    #[Layout('components.layouts.app')]
    #[Title('Proveedores')]
    public function render()
    {
        $suppliers = Supplier::withCount('vendors')
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('trade_name', 'ilike', "%{$this->search}%")
                          ->orWhere('legal_name', 'ilike', "%{$this->search}%")
                          ->orWhere('rfc', 'ilike', "%{$this->search}%");
                });
            })
            ->when($this->categoryFilter, fn ($q) => $q->where('category', $this->categoryFilter))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(12);

        $viewingSupplier = $this->viewingSupplierId
            ? Supplier::with('vendors')->find($this->viewingSupplierId)
            : null;

        $categories = Supplier::select('category')->whereNotNull('category')->distinct()->pluck('category', 'category')->toArray();

        return view('livewire.suppliers.supplier-index', compact('suppliers', 'categories', 'viewingSupplier'));
    }
}
