<?php

namespace App\Repositories;

use App\DTOs\CategoryDTO;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CategoryRepository extends BaseRepository
{
    protected string $modelClass = Category::class;

    /**
     * @param CategoryDTO $dto
     * @return Category
     */
    public function save(CategoryDTO $dto): Category
    {
        return $this->saveRecord([
            'name' => $dto->name,
        ], $dto->id);
    }
}
