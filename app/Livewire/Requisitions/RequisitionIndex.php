<?php

namespace App\Livewire\Requisitions;

use App\Models\Category;
use App\Models\Measure;
use App\Models\Product;
use App\Models\Project;
use App\Models\Requisition;
use App\Models\Supplier;
use App\Services\RequisitionItemResolverService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use App\Livewire\Concerns\WithSorting;

class RequisitionIndex extends Component
{
    use WithPagination;
    use WithSorting;

    public string $search = '';
    public string $statusFilter = '';
    public string $projectFilter = '';
    public string $periodFilter = '';
    public string $creatorFilter = '';
    public string $vendorFilter = '';

    // Selección masiva (Sprint 1)
    public array $selectedRows = [];
    public bool $selectAll = false;

    public function updatedSelectAll($value): void
    {
        if ($value) {
            // FIXME: Para Sprint 3 (Acciones masivas), esto debe usar la query filtrada.
            // Por ahora, selecciona los IDs de la vista actual como prueba de concepto.
            $this->selectedRows = Requisition::pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedRows = [];
        }
    }

    // Rechazo con comentario obligatorio (RF-REQ-09)
    public bool $showRejectModal = false;
    public ?int $rejectingId = null;
    public string $rejectionComment = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedProjectFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPeriodFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCreatorFilter(): void
    {
        $this->resetPage();
    }

    public function updatedVendorFilter(): void
    {
        $this->resetPage();
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
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Requisición aprobada automáticamente.']);
            
            if ($req->creator && $req->creator->id !== $user->id) {
                $req->creator->notify(new \App\Notifications\RequisitionStatusChanged($req, 'borrador', 'aprobada', $user));
                $this->dispatch('notification-received');
            }
        } else {
            $req->update(['status' => 'pendiente']);
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Requisición enviada a aprobación.']);
            
            $approvers = \App\Models\User::all()->filter(fn($u) => $u->hasPermission('requisiciones.aprobar') || $u->hasPermission('*'));
            \Illuminate\Support\Facades\Notification::send($approvers, new \App\Notifications\RequisitionPendingApproval($req));
            $this->dispatch('notification-received');
        }
    }

    /** RF-REQ-09: Aprobar requisición (Pendiente → Aprobada). */
    public function approve(int $requisitionId): void
    {
        if (!auth()->user()->hasPermission('requisiciones.aprobar')) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No tienes permiso para aprobar requisiciones.']);
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
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Requisición aprobada.']);
        
        if ($req->creator) {
            $req->creator->notify(new \App\Notifications\RequisitionStatusChanged($req, 'pendiente', 'aprobada', auth()->user()));
            $this->dispatch('notification-received');
        }
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
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No tienes permiso para rechazar requisiciones.']);
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
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Requisición rechazada.']);
        
        if ($req->creator) {
            $req->creator->notify(new \App\Notifications\RequisitionStatusChanged($req, 'pendiente', 'rechazada', auth()->user()));
            $this->dispatch('notification-received');
        }
    }

    public function deleteRequisition(int $id): void
    {
        Requisition::findOrFail($id)->delete();
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Requisición eliminada.']);
    }

    #[On('quotation-dismissed')]
    public function refreshCount(): void
    {
        // Triggers re-rendering to update the tab count
    }

    #[Layout('components.layouts.app')]
    #[Title('Requisiciones')]
    public function render()
    {
        $requisitions = Requisition::with(['project', 'vendor', 'creator', 'items', 'quotations'])
            ->when($this->search, fn($q) => $q->where(fn($sq) => $sq->where('number', 'like', "%{$this->search}%")->orWhere('annotations', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->projectFilter, fn($q) => $q->where('project_id', $this->projectFilter))
            ->when($this->creatorFilter, fn($q) => $q->where('created_by', $this->creatorFilter))
            ->when($this->vendorFilter, fn($q) => $q->where('vendor_id', $this->vendorFilter))
            ->when($this->periodFilter, function ($q) {
                match ($this->periodFilter) {
                    'this_month' => $q->whereMonth('date', now()->month)->whereYear('date', now()->year),
                    'last_month' => $q->whereMonth('date', now()->subMonth()->month)->whereYear('date', now()->subMonth()->year),
                    'this_quarter' => $q->whereBetween('date', [now()->startOfQuarter(), now()->endOfQuarter()]),
                    'this_year' => $q->whereYear('date', now()->year),
                    default => null,
                };
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);

        $projects = Project::where('status', 'activo')->orderBy('name')->get();
        $creators = \App\Models\User::orderBy('name')->get();
        $vendors = Supplier::orderBy('trade_name')->get();
        
        $pendingQuotations = \App\Models\Quotation::whereNull('requisition_id')
            ->where('is_orphan', false)
            ->where(function ($query) {
                $query->whereIn('status', ['pending', 'processing'])
                      ->orWhere(function ($q) {
                          $q->whereIn('status', ['completed', 'failed'])
                            ->where('created_at', '>=', now()->subDays(7));
                      });
            })
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.requisitions.requisition-index', compact(
            'requisitions',
            'projects',
            'creators',
            'vendors',
            'pendingQuotations'
        ));
    }
}
