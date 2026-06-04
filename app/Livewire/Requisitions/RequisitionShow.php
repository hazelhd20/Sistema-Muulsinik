<?php

namespace App\Livewire\Requisitions;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Requisition;

class RequisitionShow extends Component
{
    public Requisition $requisition;

    public function mount($id)
    {
        $this->requisition = Requisition::with(['project', 'creator', 'vendor', 'items.product', 'items.measure'])->findOrFail($id);
    }

    #[Layout('components.layouts.app')]
    #[Title('Detalle de Requisición')]
    public function render()
    {
        return view('livewire.requisitions.requisition-show');
    }
}
