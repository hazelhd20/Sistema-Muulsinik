<?php

namespace App\Repositories;

use App\DTOs\QuickBudgetDTO;
use App\Models\QuickBudget;
use Illuminate\Support\Facades\DB;

class QuickBudgetRepository
{
    /**
     * Saves a quick budget, creating it or updating an existing one.
     * Computes math isolated from the UI to guarantee consistency.
     */
    public function save(?int $id, QuickBudgetDTO $dto): QuickBudget
    {
        return DB::transaction(function () use ($id, $dto) {
            $subtotal = 0;
            $itemsData = [];

            foreach ($dto->items as $itemDto) {
                $lineTotal = round($itemDto->quantity * $itemDto->unit_price, 2);
                $subtotal += $lineTotal;

                $itemsData[] = [
                    'product_id' => $itemDto->product_id,
                    'concept' => $itemDto->concept,
                    'measure_id' => $itemDto->measure_id,
                    'quantity' => $itemDto->quantity,
                    'unit_price' => $itemDto->unit_price,
                    'line_total' => $lineTotal,
                ];
            }

            // Calculations
            $taxAmount = 0; // Simplified for quick budgets according to current logic
            $total = $subtotal;
            $marginAmount = $total * ($dto->margin_percent / 100);
            $grandTotal = $total + $marginAmount;

            $budget = QuickBudget::updateOrCreate(
                ['id' => $id],
                [
                    'title' => $dto->title,
                    'description' => $dto->description,
                    'client' => $dto->client,
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total' => $total,
                    'margin_percent' => $dto->margin_percent,
                    'grand_total' => $grandTotal,
                    'created_by' => $dto->created_by,
                ]
            );

            // Recreate items
            $budget->items()->delete();
            $budget->items()->createMany($itemsData);

            return $budget;
        });
    }

    /**
     * Deletes a single budget.
     */
    public function delete(int $id): bool
    {
        return QuickBudget::findOrFail($id)->delete();
    }

    /**
     * Bulk deletes budgets.
     */
    public function bulkDelete(array $ids): void
    {
        if (!empty($ids)) {
            QuickBudget::whereIn('id', $ids)->delete();
        }
    }
}
