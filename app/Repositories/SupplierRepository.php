<?php

namespace App\Repositories;

use App\DTOs\SupplierDTO;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class SupplierRepository extends BaseRepository
{
    protected string $modelClass = Supplier::class;

    /**
     * @param SupplierDTO $dto
     * @return Supplier
     */
    public function save(SupplierDTO $dto): Supplier
    {
        return $this->saveRecord([
            'trade_name' => $dto->trade_name,
            'legal_name' => $dto->legal_name,
            'rfc' => $dto->rfc,
            'category' => $dto->category,
            'notes' => $dto->notes,
            'active' => $dto->active,
        ], $dto->id);
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
