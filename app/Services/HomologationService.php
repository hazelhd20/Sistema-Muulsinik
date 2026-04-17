<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductAlias;
use App\Services\AI\GeminiStructurerService;
use Illuminate\Support\Str;

/**
 * RF-REQ-07 — Servicio de homologación de productos.
 *
 * Compara nombres extraídos de cotizaciones contra el catálogo maestro
 * (products + product_aliases) y devuelve sugerencias por similitud.
 *
 * Estrategia de búsqueda (en orden de prioridad):
 * 1. Match exacto en aliases.
 * 2. Match exacto en canonical_name.
 * 3. Búsqueda tokenizada (palabras clave independientes del orden).
 * 4. Ranking con IA (Gemini) para desambiguación si hay múltiples candidatos.
 *
 * La homologación NO es obligatoria al guardar.
 * Los ítems sin homologar se marcan como 'pending' y deberán
 * homologarse antes de la aprobación de la requisición.
 */
class HomologationService
{
    /** Umbral mínimo de similitud (0-100) para considerar un match. */
    private const SIMILARITY_THRESHOLD = 50;

    /** Umbral de similitud alta para auto-homologación sin IA. */
    private const AUTO_HOMOLOGATION_THRESHOLD = 90;

    public function __construct(
        private readonly GeminiStructurerService $gemini,
    ) {}

    /**
     * Busca candidatos del catálogo maestro similares al nombre dado.
     *
     * @return array<int, array{id: int, canonical_name: string, similarity: int}>
     */
    public function findSuggestions(string $productName, int $limit = 5): array
    {
        if (empty(trim($productName))) {
            return [];
        }

        $normalized = $this->normalize($productName);

        // 1. Match exacto en aliases → máxima prioridad
        $aliasMatch = ProductAlias::where('alias_name', $productName)->first();
        if ($aliasMatch) {
            $product = $aliasMatch->product;
            return [[
                'id'             => $product->id,
                'canonical_name' => $product->canonical_name,
                'similarity'     => 100,
            ]];
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

        // 3. Búsqueda tokenizada: dividir en palabras y buscar productos con TODAS
        $candidates = $this->tokenizedSearch($normalized, $limit * 3);

        // 4. Si hay múltiples candidatos con similitud parecida, usar IA para desambiguar
        $candidates = $this->aiRanking($productName, $candidates);

        return array_slice($candidates, 0, $limit);
    }

    /**
     * Búsqueda tokenizada: divide el nombre en palabras significativas
     * y busca productos que contengan TODAS las palabras clave,
     * independientemente del orden.
     *
     * @return array<int, array{id: int, canonical_name: string, similarity: int}>
     */
    private function tokenizedSearch(string $normalizedName, int $limit): array
    {
        // Extraer palabras significativas (ignorar las muy cortas)
        $tokens = array_filter(
            explode(' ', $normalizedName),
            fn (string $word) => mb_strlen($word) >= 3,
        );

        if (empty($tokens)) {
            // Si todas las palabras son muy cortas, usar búsqueda LIKE simple
            return $this->fallbackLikeSearch($normalizedName, $limit);
        }

        // Construir query: cada token debe estar presente en canonical_name O en aliases
        $query = Product::query();

        foreach ($tokens as $token) {
            $query->where(function ($q) use ($token) {
                $q->where('canonical_name', 'LIKE', "%{$token}%")
                  ->orWhereHas('aliases', fn ($aq) => $aq->where('alias_name', 'LIKE', "%{$token}%"));
            });
        }

        $products = $query->limit($limit)->get();

        // Si búsqueda estricta (AND) no encontró resultados, relajar a OR parcial
        if ($products->isEmpty() && count($tokens) > 1) {
            $products = $this->relaxedTokenSearch($tokens, $limit);
        }

        // Calcular similitud para cada candidato
        $candidates = [];
        foreach ($products as $product) {
            $sim = $this->calculateSimilarity($normalizedName, $this->normalize($product->canonical_name));

            // Bonus: si más tokens coinciden, subir score
            $tokenBonus = $this->calculateTokenBonus($tokens, $this->normalize($product->canonical_name));
            $finalScore = min(99, $sim + $tokenBonus);

            if ($finalScore >= self::SIMILARITY_THRESHOLD) {
                $candidates[] = [
                    'id'             => $product->id,
                    'canonical_name' => $product->canonical_name,
                    'similarity'     => $finalScore,
                ];
            }
        }

        // Ordenar por similitud descendente
        usort($candidates, fn ($a, $b) => $b['similarity'] <=> $a['similarity']);

        return $candidates;
    }

    /**
     * Búsqueda relajada: al menos N-1 tokens deben coincidir.
     */
    private function relaxedTokenSearch(array $tokens, int $limit): \Illuminate\Database\Eloquent\Collection
    {
        $query = Product::query();

        // Cada token suma un "punto", necesitamos al menos el 60% de los tokens
        $minMatches = max(1, (int) ceil(count($tokens) * 0.6));

        $query->where(function ($q) use ($tokens) {
            foreach ($tokens as $token) {
                $q->orWhere('canonical_name', 'LIKE', "%{$token}%");
            }
        });

        return $query->limit($limit)->get();
    }

    /**
     * Fallback para nombres con palabras muy cortas.
     *
     * @return array<int, array{id: int, canonical_name: string, similarity: int}>
     */
    private function fallbackLikeSearch(string $normalizedName, int $limit): array
    {
        $products = Product::where('canonical_name', 'LIKE', "%{$normalizedName}%")
            ->orWhereHas('aliases', fn ($q) => $q->where('alias_name', 'LIKE', "%{$normalizedName}%"))
            ->limit($limit)
            ->get();

        $candidates = [];
        foreach ($products as $product) {
            $sim = $this->calculateSimilarity($normalizedName, $this->normalize($product->canonical_name));
            if ($sim >= self::SIMILARITY_THRESHOLD) {
                $candidates[] = [
                    'id'             => $product->id,
                    'canonical_name' => $product->canonical_name,
                    'similarity'     => $sim,
                ];
            }
        }

        usort($candidates, fn ($a, $b) => $b['similarity'] <=> $a['similarity']);

        return $candidates;
    }

    /**
     * Calcula un bonus de similitud basado en la cantidad de tokens que coinciden.
     */
    private function calculateTokenBonus(array $tokens, string $canonicalNormalized): int
    {
        if (empty($tokens)) {
            return 0;
        }

        $matched = 0;
        foreach ($tokens as $token) {
            if (str_contains($canonicalNormalized, $token)) {
                $matched++;
            }
        }

        // Bonus proporcional: 10 puntos extra si todos coinciden
        return (int) round(($matched / count($tokens)) * 10);
    }

    /**
     * Usa Gemini AI para seleccionar el mejor match si hay ambigüedad.
     *
     * Solo se invoca cuando:
     * - Hay más de 1 candidato
     * - El primer candidato no tiene similitud suficientemente alta para auto-aceptar
     *
     * @param  array<int, array{id: int, canonical_name: string, similarity: int}> $candidates
     * @return array<int, array{id: int, canonical_name: string, similarity: int}>
     */
    private function aiRanking(string $originalName, array $candidates): array
    {
        // Si solo hay 0-1 candidatos o el primero es casi perfecto, no necesitamos IA
        if (count($candidates) <= 1) {
            return $candidates;
        }

        if (!empty($candidates[0]) && $candidates[0]['similarity'] >= self::AUTO_HOMOLOGATION_THRESHOLD) {
            return $candidates;
        }

        // Pedir a la IA que seleccione el mejor
        $bestId = $this->gemini->rankHomologationCandidates($originalName, $candidates);

        if ($bestId === null) {
            return $candidates;
        }

        // Mover el candidato elegido por la IA al primer lugar y subirle el score
        $reordered = [];
        $aiPick    = null;

        foreach ($candidates as $candidate) {
            if ($candidate['id'] === $bestId) {
                $aiPick = $candidate;
                $aiPick['similarity'] = max($candidate['similarity'], 95);
            } else {
                $reordered[] = $candidate;
            }
        }

        if ($aiPick !== null) {
            array_unshift($reordered, $aiPick);
        }

        return $reordered;
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
