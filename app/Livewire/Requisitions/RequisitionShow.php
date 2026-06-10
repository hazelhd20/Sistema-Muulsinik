<?php

namespace App\Livewire\Requisitions;

use App\Models\Requisition;
use App\Notifications\RequisitionPendingApproval;
use App\Notifications\RequisitionStatusChanged;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class RequisitionShow extends Component
{
    public int $requisitionId;

    /* ── Modal de rechazo (RF-REQ-09) ── */
    public bool   $showRejectModal   = false;
    public string $rejectionComment  = '';

    public function mount(int $id): void
    {
        $this->requisitionId = $id;
    }

    /* ═══════════════════════════════════════════════════
     *  ACCIONES DE WORKFLOW
     * ═══════════════════════════════════════════════════ */

    /**
     * RF-REQ-09 — Enviar borrador a aprobación (Borrador → Pendiente).
     * Si el usuario tiene permiso '*', la requisición se aprueba automáticamente.
     */
    public function submitForApproval(): void
    {
        $req = Requisition::findOrFail($this->requisitionId);

        if ($req->status !== 'borrador') {
            return;
        }

        $user    = auth()->user();
        $isAdmin = in_array('*', $user->role?->permissions ?? [], true);

        if ($isAdmin) {
            $req->update([
                'status'      => 'aprobada',
                'approved_by' => $user->id,
            ]);

            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Requisición aprobada automáticamente.']);

            if ($req->creator && $req->creator->id !== $user->id) {
                $req->creator->notify(
                    new RequisitionStatusChanged($req, 'borrador', 'aprobada', $user)
                );
                $this->dispatch('notification-received');
            }
        } else {
            $req->update(['status' => 'pendiente']);

            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Requisición enviada a aprobación.']);

            $approvers = \App\Models\User::all()->filter(
                fn($u) => $u->hasPermission('requisiciones.aprobar') || $u->hasPermission('*')
            );
            Notification::send($approvers, new RequisitionPendingApproval($req));
            $this->dispatch('notification-received');
        }
    }

    /**
     * RF-REQ-09 — Aprobar requisición (Pendiente → Aprobada).
     */
    public function approve(): void
    {
        if (
            !auth()->user()->hasPermission('requisiciones.aprobar')
            && !auth()->user()->hasPermission('*')
        ) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => 'No tienes permiso para aprobar requisiciones.']);
            return;
        }

        $req = Requisition::findOrFail($this->requisitionId);

        if ($req->status !== 'pendiente') {
            return;
        }

        $req->update([
            'status'      => 'aprobada',
            'approved_by' => auth()->id(),
        ]);

        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Requisición aprobada.']);

        if ($req->creator) {
            $req->creator->notify(
                new RequisitionStatusChanged($req, 'pendiente', 'aprobada', auth()->user())
            );
            $this->dispatch('notification-received');
        }
    }

    /**
     * RF-REQ-09 — Abrir modal de rechazo.
     */
    public function openRejectModal(): void
    {
        $this->rejectionComment = '';
        $this->showRejectModal  = true;
    }

    /**
     * RF-REQ-09 — Confirmar rechazo con comentario obligatorio (Pendiente → Rechazada).
     */
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

        $req = Requisition::findOrFail($this->requisitionId);

        $req->update([
            'status'             => 'rechazada',
            'approved_by'        => auth()->id(),
            'rejection_comment'  => $this->rejectionComment,
        ]);

        if ($req->creator) {
            $req->creator->notify(
                new RequisitionStatusChanged($req, 'pendiente', 'rechazada', auth()->user())
            );
            $this->dispatch('notification-received');
        }

        $this->dispatch('toast', ['icon' => 'success', 'message' => 'Requisición rechazada.']);

        $this->showRejectModal  = false;
        $this->rejectionComment = '';
    }

    /* ═══════════════════════════════════════════════════
     *  RENDER
     * ═══════════════════════════════════════════════════ */

    #[Layout('components.layouts.app')]
    #[Title('Detalle de Requisición')]
    public function render()
    {
        $requisition = Requisition::with([
            'project',
            'creator',
            'vendor',
            'approver',          // quién aprobó / rechazó
            'items.product.category',
            'items.product.measure',
            'items.measure',
        ])->findOrFail($this->requisitionId);

        return view('livewire.requisitions.requisition-show', compact('requisition'));
    }
}
