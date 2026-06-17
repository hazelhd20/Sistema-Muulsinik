<?php

namespace App\Repositories;

use App\DTOs\ProductDTO;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use App\Services\DataNormalizerService;

class ProductRepository
{
    /**
     * @param ProductDTO $dto
     * @return Product
     */
    public function save(ProductDTO $dto): Product
    {
        return DB::transaction(function () use ($dto) {
            if ($dto->id) {
                $product = Product::findOrFail($dto->id);
                // normalized_name is updated automatically by booted() in Model
                $product->update([
                    'canonical_name' => $dto->canonical_name,
                    'description' => $dto->description,
                    'category_id' => $dto->category_id,
                    'measure_id' => $dto->measure_id,
                ]);
            } else {
                $product = Product::create([
                    'canonical_name' => $dto->canonical_name,
                    'description' => $dto->description,
                    'category_id' => $dto->category_id,
                    'measure_id' => $dto->measure_id,
                ]);
            }

            return $product;
        });
    }

    /**
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            Product::findOrFail($id)->delete();
        });
    }

    /**
     * @param array $ids
     * @return void
     */
    public function bulkDelete(array $ids): void
    {
        DB::transaction(function () use ($ids) {
            Product::whereIn('id', $ids)->delete();
        });
    }
}
