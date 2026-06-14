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

        return view('livewire.requisitions.pending-quotations-list', compact('pendingQuotations'));
    }
}
