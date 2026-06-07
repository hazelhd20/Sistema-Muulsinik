<?php

namespace App\Livewire\Requisitions;

use App\Models\Quotation;
use Livewire\Component;

class PendingQuotationsList extends Component
{
    public function dismissQuotation(int $quotationId): void
    {
        $quotation = Quotation::find($quotationId);
        if ($quotation) {
            $quotation->update(['is_orphan' => true]);
            $this->dispatch('toast', ['icon' => 'success', 'message' => 'Borrador descartado.']);
            $this->dispatch('quotation-dismissed');
        }
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
