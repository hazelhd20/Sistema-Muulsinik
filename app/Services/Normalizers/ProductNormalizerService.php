<?php
namespace App\Services\Normalizers;

use App\Models\Product;
use App\Models\Category;

class ProductNormalizerService
{
    public function __construct(private TextNormalizerService $text, private FuzzyMatcherService $fuzzy, private UnitNormalizerService $unit) {}

public function normalizeProductName(string $rawName): string
    {
        $name = mb_strtoupper(trim(preg_replace('/\s+/', ' ', $rawName)));

        // Fase 1: Eliminar viñetas/numeración al inicio ("1.", "2.", "-", "•")
        $name = preg_replace('/^[\d]+\.\s*/', '', $name);
        $name = preg_replace('/^[\-•\*]\s*/', '', $name);

        // Fase 2: Eliminar caracteres basura sueltos (pipes, asteriscos sueltos)
        $name = str_replace(['|', '*'], '', $name);

        // Fase 3: Eliminar códigos SKU — estrategia de "proteger y eliminar"
        $name = $this->stripProductCodes($name);

        // Fase 4: Normalización final
        $name = trim(preg_replace('/\s+/', ' ', $name));

        return $name;
    }



public function findMatchingProduct(string $rawName): ?array
    {
        if (empty(trim($rawName))) {
            return null;
        }

        $normalized = $this->text->normalizeText($rawName);
        $cleanName = $this->stripProductCodes($normalized);

        // Fase 1a: Match exacto directo (nombre tal cual vs BD)
        $product = Product::where('normalized_name', $normalized)->first();
        if ($product) {
            return [
                'match' => $product,
                'confidence' => 1.0,
                'source' => 'exact',
            ];
        }

        // Fase 1b: Match exacto con nombre limpio (sin códigos) vs BD
        //          Cubre el caso donde la IA limpió el código pero en BD está con código, o viceversa
        if ($cleanName !== $normalized) {
            $product = Product::where('normalized_name', $cleanName)->first();
            if ($product) {
                return [
                    'match' => $product,
                    'confidence' => 0.98,
                    'source' => 'exact_cleaned',
                ];
            }
        }

        // Fase 2: Match cruzado — limpiar AMBOS lados y comparar
        //         El nombre de la IA limpio vs cada candidato en BD también limpio
        $searchTerm = mb_strlen($cleanName) >= 5 ? $cleanName : $normalized;
        $candidates = Product::where('normalized_name', 'LIKE', "%{$searchTerm}%")
            ->limit(30)
            ->get();

        foreach ($candidates as $candidate) {
            $candidateClean = $this->stripProductCodes(
                $candidate->normalized_name ?? $this->text->normalizeText($candidate->canonical_name)
            );
            if ($candidateClean === $cleanName) {
                return [
                    'match' => $candidate,
                    'confidence' => 0.95,
                    'source' => 'exact_stripped',
                ];
            }
        }

        // Fase 3: Match por tokens clave (del nombre limpio para mejor precisión)
        $tokens = $this->fuzzy->extractKeyTokens($cleanName);
        if (empty($tokens)) {
            return null;
        }

        $query = Product::query();
        foreach ($tokens as $token) {
            $query->where('normalized_name', 'LIKE', "%{$token}%");
        }
        $candidates = $query->limit(30)->get();

        // Fallback: si no hay candidatos con todos los tokens, buscar con los 2 más largos
        if ($candidates->isEmpty() && count($tokens) > 1) {
            $topTokens = collect($tokens)->sortByDesc(fn ($t) => mb_strlen($t))->take(2)->values();
            $query = Product::query();
            foreach ($topTokens as $token) {
                $query->where('normalized_name', 'LIKE', "%{$token}%");
            }
            $candidates = $query->limit(30)->get();
        }

        // Fase 4: Fuzzy ponderado — compara la versión LIMPIA contra la BD LIMPIA
        //         Esto absorbe variaciones de la IA al conservar/quitar códigos
        return $this->fuzzy->bestFuzzyMatch(
            $candidates,
            $cleanName,
            0.70,
            fn ($p) => $this->stripProductCodes(
                $p->normalized_name ?? $this->text->normalizeText($p->canonical_name)
            )
        );
    }

public function findMatchingCategory(string $rawName): ?Category
    {
        if (empty(trim($rawName))) {
            return null;
        }

        $normalized = $this->text->normalizeText($rawName);

        // 1. Match exacto: buscar directamente comparando nombres normalizados
        //    (evita REGEXP_REPLACE que no está disponible en MySQL < 8.0)
        $categories = Category::limit(100)->get();  // categorías son pocas

        foreach ($categories as $candidate) {
            if ($this->text->normalizeText($candidate->name) === $normalized) {
                return $candidate;
            }
        }

        // 2. Fuzzy match: solo sobre candidatos con prefijo común (máx 30)
        $prefix = substr($normalized, 0, 3);
        if (strlen($prefix) >= 2) {
            $candidates = Category::where('name', 'like', '%'.$prefix.'%')
                ->limit(30)
                ->get();

            $bestMatch = null;
            $bestScore = 0;

            foreach ($candidates as $candidate) {
                $candidateNormalized = $this->text->normalizeText($candidate->name);
                if ($candidateNormalized === $normalized) {
                    return $candidate;
                }
                $similarity = $this->calculateSimilarity($normalized, $candidateNormalized);
                if ($similarity > $bestScore) {
                    $bestScore = $similarity;
                    $bestMatch = $candidate;
                }
            }

            if ($bestMatch !== null && $bestScore >= 0.70) {
                return $bestMatch;
            }
        }

        return null;
    }

public function normalizeItems(array $items): array
    {
        return array_map(function (array $item) {
            // Normalizar unidad
            if (! empty($item['unit'])) {
                $item['unit'] = $this->unit->normalizeUnit($item['unit']);
            }

            // Propagar unit_name de la IA para unidades nuevas no mapeadas
            // Se preserva tal cual para usarse como hint en getUnitName()
            if (! empty($item['unit_name'])) {
                $item['unit_name'] = mb_convert_case(trim($item['unit_name']), MB_CASE_TITLE, 'UTF-8');
            }

            // Limpiar nombre: normalización determinista de producto
            // Esto absorbe las inconsistencias de la IA al quitar/dejar códigos SKU
            if (! empty($item['name'])) {
                $item['name'] = $this->normalizeProductName($item['name']);
            }

            // Limpiar categoría: Mayúscula al inicio de cada palabra
            if (! empty($item['category'])) {
                $item['category'] = $this->text->normalizeTitleCase($item['category']);
            }

            return $item;
        }, $items);
    }
}
