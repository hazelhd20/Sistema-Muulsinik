<?php

namespace App\Livewire\Requisitions;

use App\Models\Quotation;
use Livewire\Component;
use Livewire\Attributes\On;

class PendingQuotationsList extends Component
{
    #[On('refresh-pending-quotations')]
    public function refreshList(): void
    {
        // Just trigger render
    }

    public function render()
    {
        $pendingQuotations = Quotation::pendingInbox()
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.requisitions.pending-quotations-list', compact('pendingQuotations'));
    }
}
