<?php
namespace App\Services\Normalizers;

class FuzzyMatcherService
{
private function extractKeyTokens(string $normalized): array
    {
        $stopwords = ['de', 'la', 'el', 'los', 'las', 'del', 'y', 'en', 'para', 'con', 'sin', 'por'];
        $tokens = explode(' ', $normalized);

        return array_values(array_filter(
            $tokens,
            fn (string $t) => mb_strlen($t) >= 3 && ! in_array($t, $stopwords)
        ));
    }

private function bestFuzzyMatch(
        iterable $candidates,
        string $normalized,
        float $threshold = 0.70,
        ?callable $extractor = null,
    ): ?array {
        $bestMatch = null;
        $bestScore = 0;

        foreach ($candidates as $candidate) {
            $candidateNormalized = $extractor
                ? $extractor($candidate)
                : ($candidate->normalized_name ?? '');

            $similarity = $this->calculateSimilarity($normalized, $candidateNormalized);

            if ($similarity > $bestScore) {
                $bestScore = $similarity;
                $bestMatch = $candidate;
            }
        }

        if ($bestMatch !== null && $bestScore >= $threshold) {
            return [
                'match' => $bestMatch,
                'confidence' => round($bestScore, 2),
                'source' => 'fuzzy_match',
            ];
        }

        return null;
    }

private function calculateSimilarity(string $a, string $b): float
    {
        if ($a === $b) {
            return 1.0;
        }

        if (empty($a) || empty($b)) {
            return 0.0;
        }

        // Señal 1: Similitud base por caracteres
        similar_text($a, $b, $charPercent);
        $charScore = $charPercent / 100;

        // Señal 2: Tokens coincidentes (Jaccard + subset bonus)
        $tokensA = array_filter(explode(' ', $a), fn (string $w) => mb_strlen($w) >= 3);
        $tokensB = array_filter(explode(' ', $b), fn (string $w) => mb_strlen($w) >= 3);

        $tokenFinal = 0.0;
        if (! empty($tokensA) && ! empty($tokensB)) {
            $intersection = count(array_intersect($tokensA, $tokensB));
            $union = count(array_unique(array_merge($tokensA, $tokensB)));
            $jaccard = $union > 0 ? $intersection / $union : 0;

            // Subset bonus: si TODOS los tokens del más corto están en el más largo,
            // el score mínimo es alto — indica que uno es versión abreviada del otro.
            // Ej: "leticia dzul" tiene ["leticia","dzul"], ambos en ["leticia","alejandra","dzul","uh"]
            $shorter = count($tokensA) <= count($tokensB) ? $tokensA : $tokensB;
            $longer = count($tokensA) > count($tokensB) ? $tokensA : $tokensB;
            $subsetRatio = count($shorter) > 0
                ? count(array_intersect($shorter, $longer)) / count($shorter)
                : 0;

            // Si todos los tokens del corto están en el largo → score mínimo 0.82
            if ($subsetRatio >= 1.0) {
                $tokenFinal = max($jaccard, 0.82);
            } else {
                $tokenFinal = $jaccard;
            }
        }

        // Señal 3: Contención directa de subcadenas
        $containmentBonus = 0.0;
        $shorter = mb_strlen($a) <= mb_strlen($b) ? $a : $b;
        $longer = mb_strlen($a) > mb_strlen($b) ? $a : $b;
        if (mb_strlen($shorter) >= 3 && str_contains($longer, $shorter)) {
            // Un nombre es subcadena completa del otro → match muy probable
            $containmentBonus = 0.85;
        }

        // Combinación: tomar el mejor indicador entre las 3 señales
        // Token analysis y containment son más confiables que character-level
        if (! empty($tokensA) && ! empty($tokensB)) {
            $combined = ($tokenFinal * 0.55) + ($charScore * 0.45);

            return max($combined, $containmentBonus);
        }

        return max($charScore, $containmentBonus);
    }
}
