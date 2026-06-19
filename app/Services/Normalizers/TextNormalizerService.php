<?php
namespace App\Services\Normalizers;

class TextNormalizerService
{
public function normalizeText(string $text): string
    {
        $text = mb_strtolower(trim($text));

        // Quitar acentos comunes del español
        $text = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $text,
        );

        // Colapsar espacios múltiples
        $text = preg_replace('/\s+/', ' ', $text);

        // Quitar puntuación no significativa (dejar letras, números, espacios, /, -)
        $text = preg_replace('/[^\w\s\/\-]/u', '', $text);

        return trim($text);
    }

public function normalizeTitleCase(string $text): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));
        $words = explode(' ', $text);

        $lowercase = ['de', 'del', 'la', 'las', 'el', 'los', 'y', 'e', 'en', 'con', 'por', 'para', 'a', 'al', 'o', 'u'];

        $result = [];
        foreach ($words as $i => $word) {
            $lower = mb_strtolower($word, 'UTF-8');
            // Primera palabra siempre en mayúscula; partículas en minúscula salvo al inicio
            if ($i === 0 || ! in_array($lower, $lowercase)) {
                $result[] = mb_strtoupper(mb_substr($word, 0, 1, 'UTF-8'), 'UTF-8')
                    .mb_strtolower(mb_substr($word, 1, null, 'UTF-8'), 'UTF-8');
            } else {
                $result[] = $lower;
            }
        }

        return implode(' ', $result);
    }
}
