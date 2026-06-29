<?php

namespace App\Livewire\Requisitions;

use App\Livewire\Concerns\EnforcesPermissions;
use App\Livewire\Concerns\WithFilters;
use App\Livewire\Concerns\WithSorting;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\Requisition;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\RequisitionPendingApproval;
use App\Notifications\RequisitionStatusChanged;
use App\Repositories\RequisitionRepository;
use App\Services\RequisitionWorkflowService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use App\Actions\Requisitions\ExportRequisitionsCsvAction;
use App\Enums\RequisitionStatus;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class RequisitionIndex extends Component
{
    use EnforcesPermissions;
    use WithPagination;
    use WithFilters;
    use WithSorting;

    public string $search = '';

    #[Url(history: true)]
    public string $tab = 'todas';

    #[Url(history: true)]
    public string $statusFilter = '';

    #[Url(history: true)]
    public string $projectFilter = '';

    #[Url(history: true)]
    public string $periodFilter = '';

    #[Url(history: true)]
    public string $dateFrom = '';

    #[Url(history: true)]
    public string $dateTo = '';

    #[Url(history: true)]
    public string $creatorFilter = '';

    #[Url(history: true)]
    public string $vendorFilter = '';


    // Selección masiva
    public array $selectedRows = [];

    public function mount(): void
    {
        if (! auth()->user()?->hasPermission('requisiciones.ver') && ! auth()->user()?->hasPermission('*')) {
            abort(403, 'No tienes permiso para ver requisiciones.');
        }

        $this->sortField = 'date';
        $this->sortDirection = 'desc';
    }



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
        if (!auth()->user()?->hasPermission('requisiciones.aprobar') && !auth()->user()?->hasPermission('*')) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No tienes permiso para aprobar requisiciones.']);
            return;
        }

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
        if (!auth()->user()?->hasPermission('requisiciones.aprobar') && !auth()->user()?->hasPermission('*')) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No tienes permiso para rechazar requisiciones.']);
            return;
        }

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
            ->where('status', RequisitionStatus::PENDING->value)
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
                ->where('status', RequisitionStatus::PENDING->value)
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
                $this->dispatch('toast', ['icon' => 'success', 'message' => $successCount . ' requisición(es) rechazada(s).']);
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

    public function dismissQuotation(int $quotationId): void
    {
        $quotation = Quotation::find($quotationId);
        if ($quotation) {
            $quotation->update(['is_orphan' => true]);
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Borrador descartado.']);
            $this->dispatch('refresh-pending-quotations')->to(PendingQuotationsList::class);
        }
    }

    public function deleteRequisition(int $id): void
    {
        if ($this->denyUnless('requisiciones.editar', 'No tienes permiso para eliminar requisiciones.')) {
            return;
        }

        app(RequisitionRepository::class)->delete($id);
        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Requisición eliminada con éxito.']);
        $this->resetPage();
    }

    /** Aprobación masiva de requisiciones seleccionadas en estado pendiente. */
    public function approveSelected(RequisitionWorkflowService $workflowService): void
    {
        if (!auth()->user()?->hasPermission('requisiciones.aprobar') && !auth()->user()?->hasPermission('*')) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No tienes permiso para aprobar requisiciones.']);
            return;
        }

        $user = auth()->user();

        $pendingIds = Requisition::whereIn('id', $this->selectedRows)
            ->where('status', RequisitionStatus::PENDING->value)
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
            $this->dispatch('toast', ['icon' => 'success', 'message' => $successCount . ' requisición(es) aprobada(s).']);
        }
        $this->selectedRows = [];
    }

    /** Eliminación masiva de requisiciones seleccionadas en estado borrador o rechazada. */
    public function deleteSelected(): void
    {
        if ($this->denyUnless('requisiciones.editar', 'No tienes permiso para eliminar requisiciones.')) {
            return;
        }

        $deletedIds = app(RequisitionRepository::class)->bulkDelete($this->selectedRows);

        if (empty($deletedIds)) {
            $this->dispatch('toast', ['icon' => 'warning', 'message' => 'No hay requisiciones borradoras o rechazadas seleccionadas para eliminar.']);
            return;
        }

        $this->dispatch('toast', ['icon' => 'success', 'message' => count($deletedIds) . ' requisición(es) eliminada(s).']);
        $this->selectedRows = [];
    }

    /** Exportación masiva de requisiciones seleccionadas a formato CSV (Resumen). */
    public function exportCsvSummary(ExportRequisitionsCsvAction $action)
    {
        if (empty($this->selectedRows)) {
            $this->dispatch('toast', ['icon' => 'warning', 'message' => 'No hay requisiciones seleccionadas para exportar.']);
            return;
        }

        $response = $action->execute($this->selectedRows, 'summary');
        $this->selectedRows = [];
        return $response;
    }

    /** Exportación masiva de requisiciones seleccionadas a formato CSV (Detallado con Ítems). */
    public function exportCsvDetailed(ExportRequisitionsCsvAction $action)
    {
        if (empty($this->selectedRows)) {
            $this->dispatch('toast', ['icon' => 'warning', 'message' => 'No hay requisiciones seleccionadas para exportar.']);
            return;
        }

        $response = $action->execute($this->selectedRows, 'detailed');
        $this->selectedRows = [];
        return $response;
    }

    /** Exportación masiva de requisiciones seleccionadas a PDFs en un archivo ZIP (Asíncrono). */
    public function exportPdfZip()
    {
        if (empty($this->selectedRows)) {
            $this->dispatch('toast', ['icon' => 'warning', 'message' => 'No hay requisiciones seleccionadas para exportar.']);
            return;
        }

        // Despachar el Job a la cola
        \App\Jobs\ExportRequisitionsPdfZipJob::dispatch(auth()->id(), $this->selectedRows);

        $this->selectedRows = [];
        $this->dispatch('toast', ['icon' => 'info', 'message' => 'Exportación iniciada. Recibirás una notificación cuando el archivo ZIP esté listo para descargar.']);
    }

    public function getListeners(): array
    {
        $userId = auth()->id();

        $listeners = [];

        if ($userId) {
            $listeners["echo-private:App.Models.User.{$userId},.Illuminate\\Notifications\\Events\\BroadcastNotificationCreated"] = 'refreshCount';
        }

        return $listeners;
    }

    #[On('quotation-dismissed')]
    #[On('echo-private:requisitions.index,.RequisitionCreated')]
    #[On('echo-private:requisitions.index,.RequisitionUpdated')]
    #[On('echo-private:requisitions.index,.RequisitionDeleted')]
    public function refreshCount(): void
    {
        // Triggers re-rendering to update the tab count and table data instantly
    }

    #[Layout('components.layouts.app')]
    #[Title('Requisiciones')]
    public function render()
    {
        $repository = app(RequisitionRepository::class);
        $requisitions = $repository->getPaginatedWithFilters(
            search: $this->search,
            statusFilter: $this->statusFilter,
            projectFilter: $this->projectFilter,
            creatorFilter: $this->creatorFilter,
            vendorFilter: $this->vendorFilter,
            periodFilter: $this->periodFilter,
            dateFrom: $this->dateFrom,
            dateTo: $this->dateTo,
            sortField: $this->sortField,
            sortDirection: $this->sortDirection,
            perPage: $this->perPage
        );

        $projects = cache()->rememberForever('projects.activos.array', fn() => Project::where('status', 'activo')->orderBy('name')->pluck('name', 'id')->toArray());
        $creators = cache()->remember('users.all.array', 3600, fn() => User::orderBy('name')->pluck('name', 'id')->toArray());
        $vendors = cache()->rememberForever('suppliers.all.array', fn() => Supplier::orderBy('trade_name')->pluck('trade_name', 'id')->toArray());

        $pendingQuotations = Quotation::pendingInbox()->orderByDesc('created_at')->get();

        return view('livewire.requisitions.requisition-index', compact(
            'requisitions',
            'projects',
            'creators',
            'vendors',
            'pendingQuotations'
        ));
    }
}
