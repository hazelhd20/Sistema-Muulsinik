<?php

namespace App\Livewire\Requisitions;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Requisition;

class RequisitionShow extends Component
{
    public int $requisitionId;

    public function mount($id)
    {
        $this->requisitionId = $id;
    }

    #[Layout('components.layouts.app')]
    #[Title('Detalle de Requisición')]
    public function render()
    {
        $requisition = Requisition::with(['project', 'creator', 'vendor', 'items.product', 'items.measure'])->findOrFail($this->requisitionId);

        return view('livewire.requisitions.requisition-show', compact('requisition'));
    }
}
