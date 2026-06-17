<?php

namespace App\Repositories;

use App\DTOs\MeasureDTO;
use App\Models\Measure;
use Illuminate\Support\Facades\DB;

class MeasureRepository
{
    /**
     * @param MeasureDTO $dto
     * @return Measure
     */
    public function save(MeasureDTO $dto): Measure
    {
        return DB::transaction(function () use ($dto) {
            if ($dto->id) {
                $measure = Measure::findOrFail($dto->id);
                $measure->update([
                    'name' => $dto->name,
                    'abbreviation' => $dto->abbreviation,
                ]);
            } else {
                $measure = Measure::create([
                    'name' => $dto->name,
                    'abbreviation' => $dto->abbreviation,
                ]);
            }

            return $measure;
        });
    }

    /**
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            Measure::findOrFail($id)->delete();
        });
    }

    /**
     * @param array $ids
     * @return void
     */
    public function bulkDelete(array $ids): void
    {
        DB::transaction(function () use ($ids) {
            Measure::whereIn('id', $ids)->delete();
        });
    }
}
