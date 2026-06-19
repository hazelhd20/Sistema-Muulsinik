<?php

namespace App\Repositories;

use App\DTOs\MeasureDTO;
use App\Models\Measure;
use Illuminate\Support\Facades\DB;

class MeasureRepository extends BaseRepository
{
    protected string $modelClass = Measure::class;

    /**
     * @param MeasureDTO $dto
     * @return Measure
     */
    public function save(MeasureDTO $dto): Measure
    {
        return $this->saveRecord([
            'name' => $dto->name,
            'abbreviation' => $dto->abbreviation,
        ], $dto->id);
    }
}
