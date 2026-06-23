<?php

namespace App\Livewire\Requisitions;

use App\Livewire\Concerns\EnforcesPermissions;
use App\Models\Requisition;
use App\Models\User;
use App\Notifications\RequisitionPendingApproval;
use App\Notifications\RequisitionStatusChanged;
use App\Services\RequisitionWorkflowService;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class RequisitionShow extends Component
{
    use EnforcesPermissions;

    public int $requisitionId;

    /* ── Modal de rechazo (RF-REQ-09) ── */
    public bool $showRejectModal = false;

    public string $rejectionComment = '';

    public function mount(int $id): void
    {
        if (! auth()->user()?->hasPermission('requisiciones.ver') && ! auth()->user()?->hasPermission('*')) {
            abort(403, 'No tienes permiso para ver requisiciones.');
        }

        $this->requisitionId = $id;
    }

    /* ═══════════════════════════════════════════════════
     *  ACCIONES DE WORKFLOW
     * ═══════════════════════════════════════════════════ */

    /**
     * RF-REQ-09 — Enviar borrador a aprobación (Borrador → Pendiente).
     * Si el usuario tiene permiso '*', la requisición se aprueba automáticamente.
     */
    public function submitForApproval(RequisitionWorkflowService $workflowService): void
    {
        $req = Requisition::findOrFail($this->requisitionId);

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

    /**
     * RF-REQ-09 — Aprobar requisición (Pendiente → Aprobada).
     */
    public function approve(RequisitionWorkflowService $workflowService): void
    {
        $req = Requisition::findOrFail($this->requisitionId);

        try {
            $workflowService->approve($req, auth()->user());
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Requisición aprobada.']);
            $this->dispatch('notification-received');
            $this->redirect(route('requisiciones.index'), navigate: true);
        } catch (\Exception $e) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * RF-REQ-09 — Abrir modal de rechazo.
     */
    public function openRejectModal(): void
    {
        $this->rejectionComment = '';
        $this->showRejectModal = true;
    }

    /**
     * RF-REQ-09 — Confirmar rechazo con comentario obligatorio (Pendiente → Rechazada).
     */
    public function confirmReject(RequisitionWorkflowService $workflowService): void
    {
        $this->validate([
            'rejectionComment' => 'required|min:5|max:500',
        ], [
            'rejectionComment.required' => 'El motivo del rechazo es obligatorio.',
            'rejectionComment.min' => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        $req = Requisition::findOrFail($this->requisitionId);

        try {
            $workflowService->reject($req, auth()->user(), $this->rejectionComment);
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Requisición rechazada.']);
            $this->dispatch('notification-received');

            $this->showRejectModal = false;
            $this->rejectionComment = '';
            $this->redirect(route('requisiciones.index'), navigate: true);
        } catch (\Exception $e) {
            $this->dispatch('toast', ['icon' => 'error', 'message' => $e->getMessage()]);
            $this->showRejectModal = false;
        }
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
            'activities.user',
            'items.product.category',
            'items.product.measure',
            'items.measure',
        ])->findOrFail($this->requisitionId);

        return view('livewire.requisitions.requisition-show', compact('requisition'));
    }
}
