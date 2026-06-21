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
            $subtotalCost = 0;
            $subtotalSale = 0;
            $itemsData = [];

            foreach ($dto->items as $itemDto) {
                // If the item doesn't have a specific unit_price set, or we want to ensure margin logic:
                // Actually the DTO should receive the calculated unit_price from the UI.
                $lineTotalCost = round($itemDto->quantity * $itemDto->unit_cost, 2);
                $lineTotalSale = round($itemDto->quantity * $itemDto->unit_price, 2);
                
                $subtotalCost += $lineTotalCost;
                $subtotalSale += $lineTotalSale;

                $itemsData[] = [
                    'product_id' => $itemDto->product_id,
                    'concept' => $itemDto->concept,
                    'item_type' => $itemDto->item_type,
                    'measure_id' => $itemDto->measure_id,
                    'quantity' => $itemDto->quantity,
                    'unit_price' => $itemDto->unit_price,
                    'unit_cost' => $itemDto->unit_cost,
                    'margin_percent' => $itemDto->margin_percent,
                    'line_total' => $lineTotalSale,
                ];
            }

            // Calculations
            $total = $subtotalSale; // Total before taxes
            
            // Note: If margin_percent at global level is used, we can store it.
            // But if each item has its own margin, the global margin might be an average or just a target.
            
            $taxAmount = $dto->include_tax ? round($total * 0.16, 2) : 0;
            $grandTotal = $total + $taxAmount;

            $budget = QuickBudget::updateOrCreate(
                ['id' => $id],
                [
                    'title' => $dto->title,
                    'description' => $dto->description,
                    'client' => $dto->client,
                    'subtotal' => $total, // In QuickBudget, subtotal usually means before tax
                    'tax_amount' => $taxAmount,
                    'total' => $total, // In old logic, total = subtotal
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
