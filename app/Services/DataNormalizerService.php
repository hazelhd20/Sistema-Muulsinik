<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Support\Str;

/**
 * Servicio centralizado de normalización de datos.
 *
 * Responsabilidad ÚNICA: transformar datos crudos (de OCR, IA, o entrada manual)
 * en formas canónicas consistentes para todo el sistema.
 *
 * Tres capas de normalización:
 * 1. Determinista — reglas fijas sin IA (unidades, texto, proveedores)
 * 2. Resolución de identidad — fuzzy matching contra datos existentes
 * 3. (Consumidor) — la capa de UI decide qué hacer con la sugerencia
 *
 * Este servicio NO persiste datos; solo los transforma y sugiere.
 * La persistencia queda en la capa que lo invoca (QuotationWizard, Job, etc.).
 */
class DataNormalizerService
{
    /**
     * Mapeo exhaustivo de sinónimos de unidades de medida.
     *
     * Cubre variaciones comunes en cotizaciones mexicanas de construcción:
     * singular/plural, abreviaturas, con/sin punto, mayúsculas.
     * La clave es el sinónimo normalizado (lowercase, sin puntos),
     * el valor es la forma canónica del sistema.
     */
    private const UNIT_MAP = [
        // Pieza
        'pieza'  => 'pza', 'piezas' => 'pza', 'pz'     => 'pza',
        'pza'    => 'pza', 'pzas'   => 'pza', 'pzs'    => 'pza',
        'und'    => 'pza', 'unidad' => 'pza', 'unidades' => 'pza',
        'u'      => 'pza', 'pc'     => 'pza', 'pcs'    => 'pza',

        // Metro lineal
        'metro'  => 'm', 'metros' => 'm', 'mt'  => 'm',
        'mts'    => 'm', 'ml'     => 'm', 'm'   => 'm',

        // Metro cuadrado
        'metro cuadrado'  => 'm2', 'metros cuadrados' => 'm2',
        'm2'              => 'm2', 'mt2'              => 'm2',
        'mts2'            => 'm2', 'm²'               => 'm2',

        // Metro cúbico
        'metro cubico' => 'm3', 'metro cúbico' => 'm3',
        'metros cubicos' => 'm3', 'm3' => 'm3', 'mt3' => 'm3',
        'mts3' => 'm3', 'm³' => 'm3',

        // Kilogramo
        'kilogramo' => 'kg', 'kilogramos' => 'kg', 'kgs' => 'kg',
        'kilo'      => 'kg', 'kilos'      => 'kg', 'kg'  => 'kg',

        // Litro
        'litro'  => 'lt', 'litros' => 'lt', 'l'   => 'lt',
        'lts'    => 'lt', 'lt'     => 'lt',

        // Bulto
        'bulto'  => 'bulto', 'bultos' => 'bulto', 'bto' => 'bulto',
        'btos'   => 'bulto',

        // Rollo
        'rollo'  => 'rollo', 'rollos' => 'rollo', 'rll' => 'rollo',

        // Caja
        'caja'   => 'caja', 'cajas' => 'caja',

        // Paquete
        'paquete' => 'paquete', 'paquetes' => 'paquete', 'paq' => 'paquete',

        // Tonelada
        'tonelada'  => 'ton', 'toneladas' => 'ton', 'ton' => 'ton',
        'tons'      => 'ton',

        // Cubo
        'cubo' => 'cubo', 'cubos' => 'cubo',

        // Tramo
        'tramo' => 'tramo', 'tramos' => 'tramo',

        // Galón
        'galon' => 'galon', 'galón' => 'galon', 'galones' => 'galon',
        'gal'   => 'galon',

        // Servicio
        'servicio' => 'servicio', 'servicios' => 'servicio', 'srv' => 'servicio',

        // Lote
        'lote' => 'lote', 'lotes' => 'lote',
    ];

    /**
     * Sufijos legales comunes en razones sociales mexicanas.
     * Se eliminan al normalizar nombres de proveedores.
     */
    private const LEGAL_SUFFIXES = [
        's.a. de c.v.',
        'sa de cv',
        's.a de c.v.',
        'sa de c.v.',
        's.a. de cv',
        's. de r.l. de c.v.',
        's de rl de cv',
        'srl de cv',
        's.a.p.i. de c.v.',
        'sapi de cv',
        's.a.',
        'sa',
        's. de r.l.',
        's de rl',
        'srl',
    ];

    /* ═══════════════════════════════════════════════════
     *  CAPA 1: NORMALIZACIÓN DETERMINISTA
     * ═══════════════════════════════════════════════════ */

    /**
     * Normaliza una unidad de medida a su forma canónica.
     *
     * Elimina puntos, espacios extra y busca en el mapeo exhaustivo.
     * Si no encuentra coincidencia, devuelve el valor limpio tal cual.
     *
     * @param  string $rawUnit  Unidad tal como vino del OCR/IA ("PZA.", "metros", "m²")
     * @return string           Forma canónica ("pza", "m", "m2")
     */
    public function normalizeUnit(string $rawUnit): string
    {
        // Limpiar: lowercase, sin puntos, sin espacios extremos
        $clean = mb_strtolower(trim($rawUnit));
        $clean = str_replace('.', '', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);

        return self::UNIT_MAP[$clean] ?? $clean;
    }

    /**
     * Normaliza texto genérico para comparación.
     *
     * Aplica transformaciones de "canonicalización" que permiten
     * comparar dos strings que representan lo mismo pero se escriben diferente.
     * NO modifica el significado, solo la representación.
     *
     * @param  string $text  Texto crudo
     * @return string        Texto normalizado (lowercase, sin acentos, sin puntuación extra)
     */
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

    /**
     * Normaliza un nombre de proveedor eliminando razón social y ruido.
     *
     * "CEMEX S.A. de C.V."  → "cemex"
     * "Materiales Pérez"     → "materiales perez"
     * "MAT. PEREZ SA DE CV"  → "mat perez"
     *
     * @param  string $rawName  Nombre tal como vino del documento
     * @return string           Nombre limpio para comparación
     */
    public function normalizeSupplierName(string $rawName): string
    {
        $text = mb_strtolower(trim($rawName));

        // Quitar sufijos legales (del más largo al más corto para evitar matcheos parciales)
        foreach (self::LEGAL_SUFFIXES as $suffix) {
            $text = rtrim(preg_replace('/' . preg_quote($suffix, '/') . '\s*$/i', '', $text));
        }

        return $this->normalizeText($text);
    }

    /* ═══════════════════════════════════════════════════
     *  CAPA 2: RESOLUCIÓN DE IDENTIDAD (fuzzy matching)
     * ═══════════════════════════════════════════════════ */

    /**
     * Busca un proveedor existente que coincida con el nombre crudo.
     *
     * Estrategia (en orden de prioridad):
     * 1. Match exacto por trade_name normalizado
     * 2. Match exacto por RFC (si lo tenemos en el futuro)
     * 3. Fuzzy match por similitud de texto
     *
     * @param  string $rawName  Nombre crudo del proveedor (del OCR/IA)
     * @return array{supplier: Supplier, confidence: float, source: string}|null
     *         null si no se encuentra ningún candidato razonable.
     */
    public function findMatchingSupplier(string $rawName): ?array
    {
        if (empty(trim($rawName))) {
            return null;
        }

        $normalized = $this->normalizeSupplierName($rawName);

        // 1. Match exacto: trade_name normalizado
        $suppliers = Supplier::all();

        foreach ($suppliers as $supplier) {
            $supplierNormalized = $this->normalizeSupplierName($supplier->trade_name);

            if ($supplierNormalized === $normalized) {
                return [
                    'supplier'   => $supplier,
                    'confidence' => 1.0,
                    'source'     => 'exact_trade_name',
                ];
            }
        }

        // 2. Fuzzy match: buscar el mejor candidato por similitud
        $bestMatch  = null;
        $bestScore  = 0;

        foreach ($suppliers as $supplier) {
            $supplierNormalized = $this->normalizeSupplierName($supplier->trade_name);
            $similarity = $this->calculateSimilarity($normalized, $supplierNormalized);

            if ($similarity > $bestScore) {
                $bestScore = $similarity;
                $bestMatch = $supplier;
            }
        }

        // Umbral: solo sugerir si la similitud es > 70%
        if ($bestMatch !== null && $bestScore >= 0.70) {
            return [
                'supplier'   => $bestMatch,
                'confidence' => round($bestScore, 2),
                'source'     => 'fuzzy_match',
            ];
        }

        return null;
    }

    /**
     * Normaliza todos los ítems de una cotización parseada.
     *
     * Aplica normalización de Capa 1 a cada ítem:
     * - Unidad de medida → forma canónica
     * - Nombre del producto → limpieza básica de texto
     *
     * NO busca coincidencias en BD (eso es Capa 2, invocado aparte).
     *
     * @param  array $items  Lista de ítems del parser
     * @return array         Ítems con unidades y nombres normalizados
     */
    public function normalizeItems(array $items): array
    {
        return array_map(function (array $item) {
            // Normalizar unidad
            if (!empty($item['unit'])) {
                $item['unit'] = $this->normalizeUnit($item['unit']);
            }

            // Limpiar nombre (quitar espacios extra, pero preservar el texto original)
            if (!empty($item['name'])) {
                $item['name'] = trim(preg_replace('/\s+/', ' ', $item['name']));
            }

            return $item;
        }, $items);
    }

    /* ═══════════════════════════════════════════════════
     *  UTILIDADES INTERNAS
     * ═══════════════════════════════════════════════════ */

    /**
     * Calcula la similitud entre dos strings normalizados.
     *
     * Combina `similar_text()` con bonificación por tokens coincidentes
     * para mejorar la precisión en nombres del sector construcción.
     *
     * @return float  Valor entre 0.0 y 1.0
     */
    private function calculateSimilarity(string $a, string $b): float
    {
        if ($a === $b) {
            return 1.0;
        }

        if (empty($a) || empty($b)) {
            return 0.0;
        }

        // Similitud base por caracteres
        similar_text($a, $b, $charPercent);
        $charScore = $charPercent / 100;

        // Bonus por tokens coincidentes (palabras en común)
        $tokensA = array_filter(explode(' ', $a), fn (string $w) => mb_strlen($w) >= 3);
        $tokensB = array_filter(explode(' ', $b), fn (string $w) => mb_strlen($w) >= 3);

        if (!empty($tokensA) && !empty($tokensB)) {
            $intersection = count(array_intersect($tokensA, $tokensB));
            $union        = count(array_unique(array_merge($tokensA, $tokensB)));
            $tokenScore   = $union > 0 ? $intersection / $union : 0;

            // Promedio ponderado: 60% tokens, 40% caracteres
            return ($tokenScore * 0.6) + ($charScore * 0.4);
        }

        return $charScore;
    }
}
