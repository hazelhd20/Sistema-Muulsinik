<?php

namespace App\Repositories;

use App\DTOs\ProductDTO;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use App\Services\DataNormalizerService;

class ProductRepository extends BaseRepository
{
    protected string $modelClass = Product::class;

    /**
     * @param ProductDTO $dto
     * @return Product
     */
    public function save(ProductDTO $dto): Product
    {
        return $this->saveRecord([
            'canonical_name' => $dto->canonical_name,
            'description' => $dto->description,
            'category_id' => $dto->category_id,
            'measure_id' => $dto->measure_id,
            'item_type' => $dto->item_type,
        ], $dto->id);
    }
}
