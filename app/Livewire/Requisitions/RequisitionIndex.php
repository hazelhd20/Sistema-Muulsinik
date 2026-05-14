<?php

namespace App\Livewire\Requisitions;

use App\Models\Project;
use App\Models\Requisition;
use App\Models\Supplier;
use App\Services\RequisitionItemResolverService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class RequisitionIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $projectFilter = '';
    public string $periodFilter = '';
    public bool $showCreateModal = false;

    public $reqProjectId = '';
    public $reqVendorId = '';
    public string $reqAnnotations = '';
    public string $reqDate = '';

    // Ítems temporales para la nueva requisición
    public array $items = [];
    public string $itemName = '';
    public string $itemQuantity = '';
    public string $itemUnit = 'pza';
    public string $itemPrice = '';
    public $itemSupplierId = '';

    // Rechazo con comentario obligatorio (RF-REQ-09)
    public bool $showRejectModal = false;
    public ?int $rejectingId = null;
    public string $rejectionComment = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPeriodFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->reqDate = now()->format('Y-m-d');
        $this->showCreateModal = true;
    }

    public function addItem(): void
    {
        $this->validate([
            'itemName' => 'required|min:2',
            'itemQuantity' => 'required|numeric|min:0.01',
            'itemUnit' => 'required',
            'itemPrice' => 'required|numeric|min:0',
        ]);

        $this->items[] = [
            'name' => $this->itemName,
            'quantity' => (float) $this->itemQuantity,
            'unit' => $this->itemUnit,
            'unit_price' => (float) $this->itemPrice,
            'supplier_id' => $this->itemSupplierId ?: null,
        ];

        $this->itemName = '';
        $this->itemQuantity = '';
        $this->itemPrice = '';
        $this->itemSupplierId = '';
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function createRequisition(): void
    {
        $this->validate([
            'reqProjectId' => 'required|exists:projects,id',
            'reqVendorId' => 'nullable|exists:vendors,id',
            'reqAnnotations' => 'nullable|max:500',
            'reqDate' => 'required|date',
        ]);

        if (empty($this->items)) {
            session()->flash('error', 'Agrega al menos un producto a la requisición.');
            return;
        }

        // Crear requisición con todos sus items usando el servicio
        $resolver = app(RequisitionItemResolverService::class);
        $resolver->createRequisitionWithItems(
            [
                'project_id' => $this->reqProjectId,
                'vendor_id' => $this->reqVendorId ?: null,
                'annotations' => $this->reqAnnotations,
                'status' => 'borrador',
                'created_by' => auth()->id(),
                'date' => $this->reqDate,
            ],
            $this->items
        );

        $this->showCreateModal = false;
        $this->resetForm();
        session()->flash('success', 'Requisición creada como borrador.');
    }

    /** RF-REQ-09: Enviar borrador a aprobación (Borrador → Pendiente).
     *  Si el usuario es Administrador (permiso *), la requisición se aprueba automáticamente. */
    public function submitForApproval(int $requisitionId): void
    {
        $req = Requisition::findOrFail($requisitionId);
        if ($req->status !== 'borrador') {
            return;
        }

        $user = auth()->user();
        $isAdmin = in_array('*', $user->role?->permissions ?? [], true);

        if ($isAdmin) {
            $req->update([
                'status' => 'aprobada',
                'approved_by' => $user->id,
            ]);
            session()->flash('success', 'Requisición aprobada automáticamente.');
        } else {
            $req->update(['status' => 'pendiente']);
            session()->flash('success', 'Requisición enviada a aprobación.');
        }
    }

    /** RF-REQ-09: Aprobar requisición (Pendiente → Aprobada). */
    public function approve(int $requisitionId): void
    {
        if (!auth()->user()->hasPermission('requisiciones.aprobar')) {
            session()->flash('error', 'No tienes permiso para aprobar requisiciones.');
            return;
        }

        $req = Requisition::findOrFail($requisitionId);
        if ($req->status !== 'pendiente') {
            return;
        }

        $req->update([
            'status' => 'aprobada',
            'approved_by' => auth()->id(),
        ]);
        session()->flash('success', 'Requisición aprobada.');
    }

    /** RF-REQ-09: Abrir modal de rechazo (Pendiente → Rechazada). */
    public function openRejectModal(int $requisitionId): void
    {
        $this->rejectingId = $requisitionId;
        $this->rejectionComment = '';
        $this->showRejectModal = true;
    }

    /** RF-REQ-09: Rechazar con comentario obligatorio. */
    public function confirmReject(): void
    {
        if (!auth()->user()->hasPermission('requisiciones.aprobar')) {
            session()->flash('error', 'No tienes permiso para rechazar requisiciones.');
            $this->showRejectModal = false;
            return;
        }

        $this->validate([
            'rejectionComment' => 'required|min:5|max:500',
        ]);

        $req = Requisition::findOrFail($this->rejectingId);
        $req->update([
            'status' => 'rechazada',
            'approved_by' => auth()->id(),
            'rejection_comment' => $this->rejectionComment,
        ]);

        $this->showRejectModal = false;
        $this->rejectingId = null;
        $this->rejectionComment = '';
        session()->flash('success', 'Requisición rechazada.');
    }

    public function deleteRequisition(int $id): void
    {
        Requisition::findOrFail($id)->delete();
        session()->flash('success', 'Requisición eliminada.');
    }

    private function resetForm(): void
    {
        $this->reqProjectId = '';
        $this->reqVendorId = '';
        $this->reqAnnotations = '';
        $this->reqDate = '';
        $this->items = [];
        $this->itemName = '';
        $this->itemQuantity = '';
        $this->itemPrice = '';
        $this->itemSupplierId = '';
    }

    #[Layout('components.layouts.app')]
    #[Title('Requisiciones')]
    public function render()
    {
        $requisitions = Requisition::with(['project', 'vendor', 'creator', 'items', 'quotations'])
            ->when($this->search, fn($q) => $q->where(fn($sq) => $sq->where('number', 'like', "%{$this->search}%")->orWhere('annotations', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->projectFilter, fn($q) => $q->where('project_id', $this->projectFilter))
            ->when($this->periodFilter, function ($q) {
                match ($this->periodFilter) {
                    'this_month'   => $q->whereMonth('date', now()->month)->whereYear('date', now()->year),
                    'last_month'   => $q->whereMonth('date', now()->subMonth()->month)->whereYear('date', now()->subMonth()->year),
                    'this_quarter' => $q->whereBetween('date', [now()->startOfQuarter(), now()->endOfQuarter()]),
                    'this_year'    => $q->whereYear('date', now()->year),
                    default        => null,
                };
            })
            ->latest()
            ->paginate(10);

        $projects = Project::where('status', 'activo')->orderBy('name')->get();
        $suppliers = Supplier::orderBy('trade_name')->get();
        $vendors = \App\Models\Vendor::orderBy('name')->get();

        return view('livewire.requisitions.requisition-index', compact(
            'requisitions',
            'projects',
            'suppliers',
            'vendors'
        ));
    }
}
