<?php

namespace App\Actions\Requisitions;

use App\Models\Measure;
use App\Models\Product;
use App\Services\DataNormalizerService;

class ResolveProductConflictAction
{
    /**
     * Resuelve el conflicto entre lo sugerido por la IA y el catálogo.
     * Actualiza la base de datos y retorna el ítem actualizado para la UI.
     *
     * @param array $item
     * @param string $field 'category' | 'unit' | 'both'
     * @return array
     */
    public function execute(array $item, string $field): array
    {
        if (empty($item['product_id']) || empty($item['conflict'])) {
            return $item;
        }

        $product = Product::find($item['product_id']);
        if (! $product) {
            return $item;
        }

        $conflict = $item['conflict'];
        $updates = [];

        if (($field === 'category' || $field === 'both') && isset($conflict['category'])) {
            $updates['category_id'] = $conflict['category']['suggested_id'];
            $item['category_id'] = $conflict['category']['suggested_id'];
            $item['category_name'] = $conflict['category']['suggested'];
            unset($item['conflict']['category']);
        }

        if (($field === 'unit' || $field === 'both') && isset($conflict['unit'])) {
            $normalizer = app(DataNormalizerService::class);
            $suggestedUnit = $conflict['unit']['suggested'];
            $measure = Measure::where('abbreviation', $suggestedUnit)->first();
            
            if (! $measure) {
                $aiUnitName = $item['_match']['measure']['unit_name'] ?? null;
                $measure = Measure::create([
                    'name' => $normalizer->getUnitName($suggestedUnit, $aiUnitName),
                    'abbreviation' => $suggestedUnit,
                ]);
            }
            
            $updates['measure_id'] = $measure->id;
            $item['unit'] = $suggestedUnit;
            unset($item['conflict']['unit']);
        }

        if (! empty($updates)) {
            $product->update($updates);
        }

        if (empty($item['conflict'])) {
            $item['conflict'] = null;
        }

        return $item;
    }
}
