<?php

namespace App\Livewire\Requisitions;

use App\Models\Requisition;
use App\Notifications\RequisitionStatusChanged;
use Livewire\Attributes\On;
use Livewire\Component;

class RequisitionDetailDrawer extends Component
{
    public bool          $showDetailDrawer   = false;
    public ?int          $showingDetailId    = null;
    public ?Requisition  $detailRequisition  = null;

    /* ── Modal de rechazo (RF-REQ-09) ── */
    public bool   $showRejectModal  = false;
    public string $rejectionComment = '';

    /* ═══════════════════════════════════════════════════
     *  ABRIR DRAWER
     * ═══════════════════════════════════════════════════ */

    #[On('open-requisition-detail')]
    public function showDetail(int $id): void
    {
        $this->showingDetailId   = $id;
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
    public function approve(int $requisitionId): void
    {
        if (
            !auth()->user()->hasPermission('requisiciones.aprobar')
            && !auth()->user()->hasPermission('*')
        ) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No tienes permiso para aprobar requisiciones.']);
            return;
        }

        $req = Requisition::findOrFail($requisitionId);
        if ($req->status !== 'pendiente') {
            return;
        }

        $req->update([
            'status'      => 'aprobada',
            'approved_by' => auth()->id(),
        ]);

        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Requisición aprobada.']);
        $this->dispatch('requisition-updated');

        if ($req->creator) {
            $req->creator->notify(
                new RequisitionStatusChanged($req, 'pendiente', 'aprobada', auth()->user())
            );
            $this->dispatch('notification-received');
        }

        $this->showDetail($requisitionId);
    }

    /** RF-REQ-09 — Abrir modal de rechazo. */
    public function openRejectModal(): void
    {
        $this->rejectionComment = '';
        $this->showRejectModal  = true;
    }

    /** RF-REQ-09 — Confirmar rechazo con comentario obligatorio (Pendiente → Rechazada). */
    public function confirmReject(): void
    {
        if (
            !auth()->user()->hasPermission('requisiciones.aprobar')
            && !auth()->user()->hasPermission('*')
        ) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No tienes permiso para rechazar requisiciones.']);
            $this->showRejectModal = false;
            return;
        }

        $this->validate([
            'rejectionComment' => 'required|min:5|max:500',
        ], [
            'rejectionComment.required' => 'El motivo del rechazo es obligatorio.',
            'rejectionComment.min'      => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        $req = Requisition::findOrFail($this->showingDetailId);

        $req->update([
            'status'            => 'rechazada',
            'approved_by'       => auth()->id(),
            'rejection_comment' => $this->rejectionComment,
        ]);

        if ($req->creator) {
            $req->creator->notify(
                new RequisitionStatusChanged($req, 'pendiente', 'rechazada', auth()->user())
            );
            $this->dispatch('notification-received');
        }

        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Requisición rechazada.']);
        $this->dispatch('requisition-updated');

        $this->showRejectModal  = false;
        $this->rejectionComment = '';

        // Recargar el drawer con el estado actualizado
        $this->showDetail($this->showingDetailId);
    }

    /* ═══════════════════════════════════════════════════
     *  RENDER
     * ═══════════════════════════════════════════════════ */

    public function render()
    {
        return view('livewire.requisitions.requisition-detail-drawer');
    }
}
