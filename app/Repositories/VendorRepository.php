<?php

namespace App\Repositories;

use App\DTOs\VendorDTO;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;

class VendorRepository extends BaseRepository
{
    protected string $modelClass = Vendor::class;

    /**
     * @param VendorDTO $dto
     * @return Vendor
     */
    public function save(VendorDTO $dto): Vendor
    {
        return $this->saveRecord([
            'supplier_id' => $dto->supplier_id,
            'name' => $dto->name,
            'phone' => $dto->phone,
            'email' => $dto->email,
        ], $dto->id);
    }
}
