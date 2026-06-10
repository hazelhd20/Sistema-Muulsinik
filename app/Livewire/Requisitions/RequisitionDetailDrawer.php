<?php

namespace App\Livewire\Requisitions;

use App\Models\Requisition;
use App\Services\RequisitionWorkflowService;
use Livewire\Attributes\On;
use Livewire\Component;

class RequisitionDetailDrawer extends Component
{
    public bool $showDetailDrawer = false;

    public ?int $showingDetailId = null;

    public ?Requisition $detailRequisition = null;

    /* ── Modal de rechazo (RF-REQ-09) ── */
    public bool $showRejectModal = false;

    public string $rejectionComment = '';

    /* ═══════════════════════════════════════════════════
     *  ABRIR DRAWER
     * ═══════════════════════════════════════════════════ */

    #[On('open-requisition-detail')]
    public function showDetail(int $id): void
    {
        $this->showingDetailId = $id;
        $this->detailRequisition = Requisition::with([
            'project',
            'vendor',
            'creator',
            'approver',
            'items.product.category',
            'items.product.measure',
            'items.measure',
            'quotations',
        ])->find($id);
        $this->showDetailDrawer = true;
    }

    /* ═══════════════════════════════════════════════════
     *  ACCIONES DE WORKFLOW
     * ═══════════════════════════════════════════════════ */

    /** Aprobar requisición desde el drawer (Pendiente → Aprobada). */
    public function approve(int $requisitionId, RequisitionWorkflowService $workflowService): void
    {
        $req = Requisition::findOrFail($requisitionId);

        try {
            $workflowService->approve($req, auth()->user());
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Requisición aprobada.']);
            $this->dispatch('requisition-updated');
            $this->dispatch('notification-received');
            $this->showDetail($requisitionId);
        } catch (\Exception $e) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /** RF-REQ-09 — Abrir modal de rechazo. */
    public function openRejectModal(): void
    {
        $this->rejectionComment = '';
        $this->showRejectModal = true;
    }

    /** RF-REQ-09 — Confirmar rechazo con comentario obligatorio (Pendiente → Rechazada). */
    public function confirmReject(RequisitionWorkflowService $workflowService): void
    {
        $this->validate([
            'rejectionComment' => 'required|min:5|max:500',
        ], [
            'rejectionComment.required' => 'El motivo del rechazo es obligatorio.',
            'rejectionComment.min' => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        $req = Requisition::findOrFail($this->showingDetailId);

        try {
            $workflowService->reject($req, auth()->user(), $this->rejectionComment);
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Requisición rechazada.']);
            $this->dispatch('requisition-updated');
            $this->dispatch('notification-received');

            $this->showRejectModal = false;
            $this->rejectionComment = '';

            // Recargar el drawer con el estado actualizado
            $this->showDetail($this->showingDetailId);
        } catch (\Exception $e) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => $e->getMessage()]);
            $this->showRejectModal = false;
        }
    }

    /* ═══════════════════════════════════════════════════
     *  RENDER
     * ═══════════════════════════════════════════════════ */

    public function render()
    {
        return view('livewire.requisitions.requisition-detail-drawer');
    }
}
