<?php

namespace App\Repositories;

use App\DTOs\SupplierDTO;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class SupplierRepository
{
    /**
     * @param SupplierDTO $dto
     * @return Supplier
     */
    public function save(SupplierDTO $dto): Supplier
    {
        return DB::transaction(function () use ($dto) {
            if ($dto->id) {
                $supplier = Supplier::findOrFail($dto->id);
                $supplier->update([
                    'trade_name' => $dto->trade_name,
                    'legal_name' => $dto->legal_name,
                    'rfc' => $dto->rfc,
                    'category' => $dto->category,
                    'notes' => $dto->notes,
                    'active' => $dto->active,
                ]);
            } else {
                $supplier = Supplier::create([
                    'trade_name' => $dto->trade_name,
                    'legal_name' => $dto->legal_name,
                    'rfc' => $dto->rfc,
                    'category' => $dto->category,
                    'notes' => $dto->notes,
                    'active' => $dto->active,
                ]);
            }

            return $supplier;
        });
    }

    /**
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            Supplier::findOrFail($id)->delete();
        });
    }

    /**
     * Toggle the active status of a supplier
     * 
     * @param int $id
     * @return Supplier
     */
    public function toggleActive(int $id): Supplier
    {
        return DB::transaction(function () use ($id) {
            $supplier = Supplier::findOrFail($id);
            $supplier->update(['active' => !$supplier->active]);
            return $supplier;
        });
    }
}
