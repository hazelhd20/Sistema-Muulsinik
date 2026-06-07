<?php

namespace App\Livewire\Requisitions;

use App\Models\Requisition;
use Livewire\Component;
use Livewire\Attributes\On;

class RequisitionDetailDrawer extends Component
{
    public bool $showDetailDrawer = false;
    public ?int $showingDetailId = null;
    public ?Requisition $detailRequisition = null;

    #[On('open-requisition-detail')]
    public function showDetail(int $id): void
    {
        $this->showingDetailId = $id;
        $this->detailRequisition = Requisition::with([
            'project', 
            'vendor', 
            'creator', 
            'items.product.category', 
            'items.product.measure', 
            'quotations'
        ])->find($id);
        $this->showDetailDrawer = true;
    }

    /** Aprobar requisición desde el drawer (Pendiente → Aprobada). */
    public function approve(int $requisitionId): void
    {
        if (!auth()->user()->hasPermission('requisiciones.aprobar') && !auth()->user()->hasPermission('*')) {
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
        $this->dispatch('requisition-updated');

        if ($req->creator) {
            $req->creator->notify(new \App\Notifications\RequisitionStatusChanged($req, 'pendiente', 'aprobada', auth()->user()));
            $this->dispatch('notification-received');
        }

        // Recargar el detalle en el drawer
        $this->showDetail($requisitionId);
    }

    public function render()
    {
        return view('livewire.requisitions.requisition-detail-drawer');
    }
}
