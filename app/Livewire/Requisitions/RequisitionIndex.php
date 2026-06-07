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

    // Selección masiva
    public array $selectedRows = [];

    // Rechazo con comentario obligatorio (RF-REQ-09)
    public bool $showRejectModal = false;
    public bool $isBulkReject = false;
    public ?int $rejectingId = null;
    public string $rejectionComment = '';

    #[On('requisition-updated')]
    public function refreshList(): void
    {
        // Esto simplemente forzará un re-render del componente index
    }

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
        $this->isBulkReject = false;
        $this->rejectingId = $requisitionId;
        $this->rejectionComment = '';
        $this->showRejectModal = true;
    }

    /** Abrir modal de rechazo en lote. */
    public function openBulkRejectModal(): void
    {
        if (!auth()->user()->hasPermission('requisiciones.aprobar') && !auth()->user()->hasPermission('*')) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No tienes permiso para rechazar requisiciones.']);
            return;
        }

        $pendingIds = Requisition::whereIn('id', $this->selectedRows)
            ->where('status', 'pendiente')
            ->pluck('id')
            ->toArray();

        if (empty($pendingIds)) {
            $this->dispatch('toast', ['icon' => 'warning', 'message' => 'No hay requisiciones pendientes seleccionadas para rechazar.']);
            return;
        }

        $this->isBulkReject = true;
        $this->rejectingId = null;
        $this->rejectionComment = '';
        $this->showRejectModal = true;
    }

    /** RF-REQ-09: Rechazar con comentario obligatorio (individual o masivo). */
    public function confirmReject(): void
    {
        if (!auth()->user()->hasPermission('requisiciones.aprobar') && !auth()->user()->hasPermission('*')) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No tienes permiso para rechazar requisiciones.']);
            $this->showRejectModal = false;
            return;
        }

        $this->validate([
            'rejectionComment' => 'required|min:5|max:500',
        ]);

        if ($this->isBulkReject) {
            $pendingIds = Requisition::whereIn('id', $this->selectedRows)
                ->where('status', 'pendiente')
                ->pluck('id')
                ->toArray();

            $reqs = Requisition::whereIn('id', $pendingIds)->get();
            foreach ($reqs as $req) {
                $req->update([
                    'status' => 'rechazada',
                    'approved_by' => auth()->id(),
                    'rejection_comment' => $this->rejectionComment,
                ]);

                if ($req->creator) {
                    $req->creator->notify(new \App\Notifications\RequisitionStatusChanged($req, 'pendiente', 'rechazada', auth()->user()));
                }
            }

            $this->dispatch('notification-received');
            $this->dispatch('toast', ['icon' => 'success', 'message' => count($pendingIds) . ' requisición(es) rechazada(s).']);
            $this->selectedRows = [];
        } else {
            $req = Requisition::findOrFail($this->rejectingId);
            $req->update([
                'status' => 'rechazada',
                'approved_by' => auth()->id(),
                'rejection_comment' => $this->rejectionComment,
            ]);

            if ($req->creator) {
                $req->creator->notify(new \App\Notifications\RequisitionStatusChanged($req, 'pendiente', 'rechazada', auth()->user()));
                $this->dispatch('notification-received');
            }
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Requisición rechazada.']);
        }

        $this->showRejectModal = false;
        $this->isBulkReject = false;
        $this->rejectingId = null;
        $this->rejectionComment = '';
    }

    public function deleteRequisition(int $id): void
    {
        Requisition::findOrFail($id)->delete();
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Requisición eliminada.']);
    }

    /** Aprobación masiva de requisiciones seleccionadas en estado pendiente. */
    public function approveSelected(): void
    {
        if (!auth()->user()->hasPermission('requisiciones.aprobar') && !auth()->user()->hasPermission('*')) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No tienes permiso para aprobar requisiciones.']);
            return;
        }

        $pendingIds = Requisition::whereIn('id', $this->selectedRows)
            ->where('status', 'pendiente')
            ->pluck('id')
            ->toArray();

        if (empty($pendingIds)) {
            $this->dispatch('toast', ['icon' => 'warning', 'message' => 'No hay requisiciones pendientes seleccionadas para aprobar.']);
            return;
        }

        $reqs = Requisition::whereIn('id', $pendingIds)->get();
        foreach ($reqs as $req) {
            $req->update([
                'status' => 'aprobada',
                'approved_by' => auth()->id(),
            ]);

            if ($req->creator) {
                $req->creator->notify(new \App\Notifications\RequisitionStatusChanged($req, 'pendiente', 'aprobada', auth()->user()));
            }
        }

        $this->dispatch('notification-received');
        $this->dispatch('toast', ['icon' => 'success', 'message' => count($pendingIds) . ' requisición(es) aprobada(s).']);
        $this->selectedRows = [];
    }

    /** Eliminación masiva de requisiciones seleccionadas en estado borrador o rechazada. */
    public function deleteSelected(): void
    {
        $deletableIds = Requisition::whereIn('id', $this->selectedRows)
            ->whereIn('status', ['borrador', 'rechazada'])
            ->pluck('id')
            ->toArray();

        if (empty($deletableIds)) {
            $this->dispatch('toast', ['icon' => 'warning', 'message' => 'No hay requisiciones borradoras o rechazadas seleccionadas para eliminar.']);
            return;
        }

        Requisition::whereIn('id', $deletableIds)->delete();
        $this->dispatch('toast', ['icon' => 'success', 'message' => count($deletableIds) . ' requisición(es) eliminada(s).']);
        $this->selectedRows = [];
    }

    /** Exportación masiva de requisiciones seleccionadas a formato CSV. */
    public function exportSelected()
    {
        if (empty($this->selectedRows)) {
            $this->dispatch('toast', ['icon' => 'warning', 'message' => 'No hay requisiciones seleccionadas para exportar.']);
            return;
        }

        $requisitions = Requisition::with(['project', 'vendor', 'creator', 'approver'])
            ->whereIn('id', $this->selectedRows)
            ->get();

        $headers = [
            'Content-type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=requisiciones_export_' . now()->format('Ymd_His') . '.csv',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0'
        ];

        $columns = ['Folio', 'Proyecto', 'Fecha', 'Creador', 'Proveedor', 'Total', 'Estado', 'Aprobado Por'];

        $callback = function() use($requisitions, $columns) {
            $file = fopen('php://output', 'w');
            // Añadir BOM de UTF-8 para compatibilidad nativa con Excel en español
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, $columns);

            foreach ($requisitions as $req) {
                fputcsv($file, [
                    $req->number ?? 'REQ-' . str_pad($req->id, 5, '0', STR_PAD_LEFT),
                    $req->project->name ?? '—',
                    $req->date?->format('d/m/Y') ?? '—',
                    $req->creator->name ?? '—',
                    $req->vendor->name ?? '—',
                    $req->total,
                    ucfirst($req->status),
                    $req->approver->name ?? '—'
                ]);
            }

            fclose($file);
        };

        $this->selectedRows = []; // Limpiar selección tras exportación
        return response()->streamDownload($callback, 'requisiciones_export_' . now()->format('Ymd_His') . '.csv', $headers);
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
