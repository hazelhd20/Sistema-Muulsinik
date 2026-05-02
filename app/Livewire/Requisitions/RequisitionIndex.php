<?php

namespace App\Livewire\Requisitions;

use App\Models\Product;
use App\Models\Project;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\Supplier;
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
    public bool $showCreateModal = false;

    // Campos de la requisición
    public $reqProjectId = '';
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

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->reqDate = now()->format('Y-m-d');
        // RF-AUTH-03: Prellenar con el proyecto activo del selector global
        $this->reqProjectId = session('active_project_id', '');
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
            'reqAnnotations' => 'nullable|max:500',
            'reqDate' => 'required|date',
        ]);

        if (empty($this->items)) {
            session()->flash('error', 'Agrega al menos un producto a la requisición.');
            return;
        }

        // RF-REQ-09: Nuevas requisiciones inician como borrador
        $requisition = Requisition::create([
            'project_id' => $this->reqProjectId,
            'annotations' => $this->reqAnnotations,
            'status' => 'borrador',
            'created_by' => auth()->id(),
            'date' => $this->reqDate,
        ]);

        foreach ($this->items as $item) {
            RequisitionItem::create([
                'requisition_id' => $requisition->id,
                'product_name' => $item['name'],
                'quantity' => $item['quantity'],
                'unit' => $item['unit'],
                'unit_price' => $item['unit_price'],
                'supplier_id' => $item['supplier_id'],
            ]);
        }

        $this->showCreateModal = false;
        $this->resetForm();
        session()->flash('success', 'Requisición creada como borrador.');
    }

    /** RF-REQ-09: Enviar borrador a aprobación (Borrador → Pendiente). */
    public function submitForApproval(int $requisitionId): void
    {
        $req = Requisition::findOrFail($requisitionId);
        if ($req->status !== 'borrador') {
            return;
        }
        $req->update(['status' => 'pendiente']);
        session()->flash('success', 'Requisición enviada a aprobación.');
    }

    /** RF-REQ-09: Aprobar requisición (Pendiente → Aprobada). */
    public function approve(int $requisitionId): void
    {
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
        $requisitions = Requisition::with(['project', 'creator', 'items'])
            ->when($this->search, fn ($q) => $q->where(fn($sq) => $sq->where('number', 'like', "%{$this->search}%")->orWhere('annotations', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->projectFilter, fn ($q) => $q->where('project_id', $this->projectFilter))
            ->latest()
            ->paginate(10);

        $projects = Project::where('status', 'activo')->orderBy('name')->get();
        $suppliers = Supplier::orderBy('trade_name')->get();

        return view('livewire.requisitions.requisition-index', compact(
            'requisitions', 'projects', 'suppliers'
        ));
    }
}
