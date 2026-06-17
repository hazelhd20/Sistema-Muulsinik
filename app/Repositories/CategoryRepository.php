<?php

namespace App\Repositories;

use App\DTOs\CategoryDTO;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CategoryRepository
{
    /**
     * @param CategoryDTO $dto
     * @return Category
     */
    public function save(CategoryDTO $dto): Category
    {
        return DB::transaction(function () use ($dto) {
            if ($dto->id) {
                $category = Category::findOrFail($dto->id);
                $category->update([
                    'name' => $dto->name,
                ]);
            } else {
                $category = Category::create([
                    'name' => $dto->name,
                ]);
            }

            return $category;
        });
    }

    /**
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            Category::findOrFail($id)->delete();
        });
    }

    /**
     * @param array $ids
     * @return void
     */
    public function bulkDelete(array $ids): void
    {
        DB::transaction(function () use ($ids) {
            Category::whereIn('id', $ids)->delete();
        });
    }
}
