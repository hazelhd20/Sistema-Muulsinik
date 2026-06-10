<?php

namespace App\Livewire\Requisitions;

use App\Livewire\Concerns\WithSorting;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\Requisition;
use App\Models\Supplier;
use App\Models\User;
use App\Notifications\RequisitionPendingApproval;
use App\Notifications\RequisitionStatusChanged;
use App\Services\RequisitionWorkflowService;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

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
    public function submitForApproval(int $requisitionId, RequisitionWorkflowService $workflowService): void
    {
        $req = Requisition::findOrFail($requisitionId);
        try {
            $result = $workflowService->submitForApproval($req, auth()->user());
            $this->dispatch('toast', [
                'icon' => 'success',
                'message' => $result['message'],
            ]);
            $this->dispatch('notification-received');
        } catch (\Exception $e) {
            $this->dispatch('toast', [
                'icon' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /** RF-REQ-09: Aprobar requisición (Pendiente → Aprobada). */
    public function approve(int $requisitionId, RequisitionWorkflowService $workflowService): void
    {
        $req = Requisition::findOrFail($requisitionId);
        try {
            $workflowService->approve($req, auth()->user());
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Requisición aprobada.']);
            $this->dispatch('notification-received');
        } catch (\Exception $e) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => $e->getMessage()]);
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
        if (! auth()->user()->hasPermission('requisiciones.aprobar') && ! auth()->user()->hasPermission('*')) {
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
    public function confirmReject(RequisitionWorkflowService $workflowService): void
    {
        $this->validate([
            'rejectionComment' => 'required|min:5|max:500',
        ]);

        $user = auth()->user();

        if ($this->isBulkReject) {
            $pendingIds = Requisition::whereIn('id', $this->selectedRows)
                ->where('status', 'pendiente')
                ->pluck('id')
                ->toArray();

            $reqs = Requisition::whereIn('id', $pendingIds)->get();
            $successCount = 0;
            foreach ($reqs as $req) {
                try {
                    $workflowService->reject($req, $user, $this->rejectionComment);
                    $successCount++;
                } catch (\Exception $e) {
                    // Si alguna falla, simplemente continuamos con las demás.
                }
            }

            if ($successCount > 0) {
                $this->dispatch('notification-received');
                $this->dispatch('toast', ['icon' => 'success', 'message' => $successCount.' requisición(es) rechazada(s).']);
            }
            $this->selectedRows = [];
        } else {
            $req = Requisition::findOrFail($this->rejectingId);
            try {
                $workflowService->reject($req, $user, $this->rejectionComment);
                $this->dispatch('toast', ['icon' => 'success', 'message' => 'Requisición rechazada.']);
                $this->dispatch('notification-received');
            } catch (\Exception $e) {
                $this->dispatch('toast', ['icon' => 'error', 'message' => $e->getMessage()]);
            }
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
    public function approveSelected(RequisitionWorkflowService $workflowService): void
    {
        $user = auth()->user();

        $pendingIds = Requisition::whereIn('id', $this->selectedRows)
            ->where('status', 'pendiente')
            ->pluck('id')
            ->toArray();

        if (empty($pendingIds)) {
            $this->dispatch('toast', ['icon' => 'warning', 'message' => 'No hay requisiciones pendientes seleccionadas para aprobar.']);

            return;
        }

        $reqs = Requisition::whereIn('id', $pendingIds)->get();
        $successCount = 0;
        foreach ($reqs as $req) {
            try {
                $workflowService->approve($req, $user);
                $successCount++;
            } catch (\Exception $e) {
                // Si alguna falla, continuamos con las demás
            }
        }

        if ($successCount > 0) {
            $this->dispatch('notification-received');
            $this->dispatch('toast', ['icon' => 'success', 'message' => $successCount.' requisición(es) aprobada(s).']);
        }
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
        $this->dispatch('toast', ['icon' => 'success', 'message' => count($deletableIds).' requisición(es) eliminada(s).']);
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
            'Content-type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=requisiciones_export_'.now()->format('Ymd_His').'.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['Folio', 'Proyecto', 'Fecha', 'Creador', 'Proveedor', 'Total', 'Estado', 'Aprobado Por'];

        $callback = function () use ($requisitions, $columns) {
            $file = fopen('php://output', 'w');
            // Añadir BOM de UTF-8 para compatibilidad nativa con Excel en español
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, $columns);

            foreach ($requisitions as $req) {
                fputcsv($file, [
                    $req->number ?? 'REQ-'.str_pad($req->id, 5, '0', STR_PAD_LEFT),
                    $req->project->name ?? '—',
                    $req->date?->format('d/m/Y') ?? '—',
                    $req->creator->name ?? '—',
                    $req->vendor->name ?? '—',
                    $req->total,
                    ucfirst($req->status),
                    $req->approver->name ?? '—',
                ]);
            }

            fclose($file);
        };

        $this->selectedRows = []; // Limpiar selección tras exportación

        return response()->streamDownload($callback, 'requisiciones_export_'.now()->format('Ymd_His').'.csv', $headers);
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
        $requisitions = Requisition::with(['project', 'vendor', 'creator', 'quotations'])->withCount('items')
            ->when($this->search, fn ($q) => $q->where(fn ($sq) => $sq->where('number', 'like', "%{$this->search}%")->orWhere('annotations', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->projectFilter, fn ($q) => $q->where('project_id', $this->projectFilter))
            ->when($this->creatorFilter, fn ($q) => $q->where('created_by', $this->creatorFilter))
            ->when($this->vendorFilter, fn ($q) => $q->where('vendor_id', $this->vendorFilter))
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
        $creators = User::orderBy('name')->get();
        $vendors = Supplier::orderBy('trade_name')->get();

        $pendingQuotations = Quotation::whereNull('requisition_id')
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
