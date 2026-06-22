<?php

namespace App\Observers;

use App\Models\RequisitionItem;

class RequisitionItemObserver
{
    /**
     * Handle the RequisitionItem "saved" event.
     */
    public function saved(RequisitionItem $item): void
    {
        if ($item->requisition) {
            $item->requisition->recalculateTotals();
        }
    }

    /**
     * Handle the RequisitionItem "deleted" event.
     */
    public function deleted(RequisitionItem $item): void
    {
        if ($item->requisition) {
            $item->requisition->recalculateTotals();
        }
    }
}
