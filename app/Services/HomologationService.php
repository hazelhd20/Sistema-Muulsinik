<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductAlias;
use Illuminate\Support\Str;

/**
 * RF-REQ-07 — Servicio de homologación de productos.
 *
 * Compara nombres extraídos de cotizaciones contra el catálogo maestro
 * (products + product_aliases) y devuelve sugerencias por similitud.
 *
 * La homologación NO es obligatoria al guardar.
 * Los ítems sin homologar se marcan como 'pending' y deberán
 * homologarse antes de la aprobación de la requisición.
 */
class HomologationService
{
    /** Umbral mínimo de similitud (0-100) para considerar un match. */
    private const SIMILARITY_THRESHOLD = 60;

    /**
     * Busca candidatos del catálogo maestro similares al nombre dado.
     *
     * @return array<int, array{id: int, canonical_name: string, similarity: int}>
     */
    public function findSuggestions(string $productName, int $limit = 5): array
    {
        $normalized = $this->normalize($productName);
        $candidates = [];

        // 1. Match exacto en aliases → máxima prioridad
        $aliasMatch = ProductAlias::where('alias_name', $productName)->first();
        if ($aliasMatch) {
            $product      = $aliasMatch->product;
            $candidates[] = [
                'id'             => $product->id,
                'canonical_name' => $product->canonical_name,
                'similarity'     => 100,
            ];
            return $candidates;
        }

        // 2. Match exacto en canonical_name
        $exactProduct = Product::where('canonical_name', $productName)->first();
        if ($exactProduct) {
            return [[
                'id'             => $exactProduct->id,
                'canonical_name' => $exactProduct->canonical_name,
                'similarity'     => 100,
            ]];
        }

        // 3. Búsqueda por similitud (LIKE + Levenshtein)
        $products = Product::where('canonical_name', 'LIKE', "%{$normalized}%")
            ->orWhereHas('aliases', fn ($q) => $q->where('alias_name', 'LIKE', "%{$normalized}%"))
            ->limit(50)
            ->get();

        foreach ($products as $product) {
            $sim = $this->calculateSimilarity($normalized, $this->normalize($product->canonical_name));
            if ($sim >= self::SIMILARITY_THRESHOLD) {
                $candidates[] = [
                    'id'             => $product->id,
                    'canonical_name' => $product->canonical_name,
                    'similarity'     => $sim,
                ];
            }
        }

        // Ordenar por similitud descendente
        usort($candidates, fn ($a, $b) => $b['similarity'] <=> $a['similarity']);

        return array_slice($candidates, 0, $limit);
    }

    /**
     * Homologa un ítem: vincula el product_id y marca como homologado.
     * Opcionalmente crea un alias para futuras coincidencias.
     */
    public function homologate(
        \App\Models\RequisitionItem $item,
        int $productId,
        bool $createAlias = true,
    ): void {
        $item->update([
            'product_id'          => $productId,
            'homologation_status' => 'homologated',
        ]);

        // Crear alias para el nombre original si no existe ya
        if ($createAlias && $item->product_name) {
            ProductAlias::firstOrCreate([
                'alias_name'  => $item->product_name,
                'product_id'  => $productId,
            ], [
                'supplier_id' => $item->supplier_id,
            ]);
        }
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/\s+/', ' ', $text);
        return $text;
    }

    private function calculateSimilarity(string $a, string $b): int
    {
        similar_text($a, $b, $percent);
        return (int) round($percent);
    }
}
