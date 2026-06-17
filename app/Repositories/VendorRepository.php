<?php

namespace App\Repositories;

use App\DTOs\VendorDTO;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;

class VendorRepository
{
    /**
     * @param VendorDTO $dto
     * @return Vendor
     */
    public function save(VendorDTO $dto): Vendor
    {
        return DB::transaction(function () use ($dto) {
            if ($dto->id) {
                $vendor = Vendor::findOrFail($dto->id);
                $vendor->update([
                    'supplier_id' => $dto->supplier_id,
                    'name' => $dto->name,
                    'phone' => $dto->phone,
                    'email' => $dto->email,
                ]);
            } else {
                $vendor = Vendor::create([
                    'supplier_id' => $dto->supplier_id,
                    'name' => $dto->name,
                    'phone' => $dto->phone,
                    'email' => $dto->email,
                ]);
            }

            return $vendor;
        });
    }

    /**
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            Vendor::findOrFail($id)->delete();
        });
    }
}
