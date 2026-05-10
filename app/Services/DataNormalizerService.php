<?php

namespace App\Services;

use App\Models\Measure;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Vendor;
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

        // Tambor
        'tambor' => 'tambor', 'tambores' => 'tambor', 'tbr' => 'tambor',

        // Cubeta
        'cubeta' => 'cubeta', 'cubetas' => 'cubeta', 'cta' => 'cubeta',

        // Millar
        'millar' => 'millar', 'millares' => 'millar', 'mil' => 'millar',

        // Atado
        'atado' => 'atado', 'atados' => 'atado',
    ];

    /**
     * Nombres completos canónicos para las unidades de medida del sistema.
     * Mapea la abreviatura canónica (devuelta por normalizeUnit) a su nombre completo formal.
     */
    private const UNIT_NAMES = [
        'pza' => 'Pieza',
        'm' => 'Metro lineal',
        'm2' => 'Metro cuadrado',
        'm3' => 'Metro cúbico',
        'kg' => 'Kilogramo',
        'lt' => 'Litro',
        'bulto' => 'Bulto',
        'rollo' => 'Rollo',
        'caja' => 'Caja',
        'paquete' => 'Paquete',
        'ton' => 'Tonelada',
        'cubo' => 'Cubo',
        'tramo' => 'Tramo',
        'galon' => 'Galón',
        'servicio' => 'Servicio',
        'lote' => 'Lote',
        'tambor' => 'Tambor',
        'cubeta' => 'Cubeta',
        'millar' => 'Millar',
        'atado' => 'Atado',
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
     * Obtiene el nombre completo formal para una unidad canónica (abreviada).
     *
     * @param  string $canonicalUnit  Unidad canónica (e.g., "pza", "m2")
     * @return string                 Nombre completo (e.g., "Pieza", "Metro cuadrado")
     */
    public function getUnitName(string $canonicalUnit): string
    {
        return self::UNIT_NAMES[$canonicalUnit] ?? ucfirst($canonicalUnit);
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

    /**
     * Normaliza un nombre de vendedor (persona).
     *
     * @param  string $rawName
     * @return string
     */
    public function normalizeVendorName(string $rawName): string
    {
        return $this->normalizeText($rawName);
    }

    /* ═══════════════════════════════════════════════════
     *  CAPA 2: RESOLUCIÓN DE IDENTIDAD (fuzzy matching)
     * ═══════════════════════════════════════════════════ */

    /**
     * Busca un proveedor existente que coincida con el nombre crudo.
     *
     * Estrategia de 3 fases (en orden de prioridad):
     * 1. Match exacto por normalized_name (usa índice de BD — O(log n))
     * 2. Match por tokens clave significativos
     * 3. Fuzzy match sobre candidatos filtrados
     *
     * @param  string $rawName  Nombre crudo del proveedor (del OCR/IA)
     * @return array{match: Supplier, confidence: float, source: string}|null
     *         null si no se encuentra ningún candidato razonable.
     */
    public function findMatchingSupplier(string $rawName): ?array
    {
        if (empty(trim($rawName))) {
            return null;
        }

        $normalized = $this->normalizeSupplierName($rawName);

        // Fase 1: Match exacto usando índice de BD (O(log n))
        $supplier = Supplier::where('normalized_name', $normalized)->first();
        if ($supplier) {
            return [
                'match'      => $supplier,
                'confidence' => 1.0,
                'source'     => 'exact',
            ];
        }

        // Fase 2: Match por tokens clave — más robusto que prefijo
        $tokens = $this->extractKeyTokens($normalized);
        if (empty($tokens)) {
            return null;
        }

        $query = Supplier::query();
        foreach ($tokens as $token) {
            $query->where('normalized_name', 'LIKE', "%{$token}%");
        }
        $candidates = $query->limit(30)->get();

        // Fallback: si no hay candidatos con todos los tokens, buscar con el más largo
        if ($candidates->isEmpty() && count($tokens) > 1) {
            $longestToken = collect($tokens)->sortByDesc(fn($t) => mb_strlen($t))->first();
            $candidates = Supplier::where('normalized_name', 'LIKE', "%{$longestToken}%")
                ->limit(30)
                ->get();
        }

        // Fase 3: Fuzzy match sobre candidatos filtrados
        return $this->bestFuzzyMatch($candidates, $normalized, 0.70);
    }

    /**
     * Busca una categoría existente que coincida con el nombre crudo.
     *
     * @param  string $rawName  Nombre de la categoría (del OCR/IA)
     * @return \App\Models\Category|null
     */
    public function findMatchingCategory(string $rawName): ?\App\Models\Category
    {
        if (empty(trim($rawName))) {
            return null;
        }

        $normalized = $this->normalizeText($rawName);

        // 1. Match exacto: buscar directamente comparando nombres normalizados
        //    (evita REGEXP_REPLACE que no está disponible en MySQL < 8.0)
        $categories = \App\Models\Category::limit(100)->get();  // categorías son pocas

        foreach ($categories as $candidate) {
            if ($this->normalizeText($candidate->name) === $normalized) {
                return $candidate;
            }
        }

        // 2. Fuzzy match: solo sobre candidatos con prefijo común (máx 30)
        $prefix = substr($normalized, 0, 3);
        if (strlen($prefix) >= 2) {
            $candidates = \App\Models\Category::where('name', 'like', '%' . $prefix . '%')
                ->limit(30)
                ->get();

            $bestMatch = null;
            $bestScore = 0;

            foreach ($candidates as $candidate) {
                $candidateNormalized = $this->normalizeText($candidate->name);
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

    /**
     * Busca un vendedor existente que coincida con el nombre crudo.
     *
     * Busca dentro del scope del proveedor indicado.
     * Usa la misma estrategia de 3 fases que findMatchingSupplier.
     *
     * @param  string $rawName    Nombre crudo del vendedor (del OCR/IA)
     * @param  int|null $supplierId  ID del proveedor para filtrar
     * @return array{match: Vendor, confidence: float, source: string}|null
     */
    public function findMatchingVendor(string $rawName, ?int $supplierId = null): ?array
    {
        if (empty(trim($rawName))) {
            return null;
        }

        $normalized = $this->normalizeVendorName($rawName);

        // Fase 1: Match exacto por nombre normalizado
        $query = Vendor::query();
        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $allVendors = $query->limit(100)->get();

        foreach ($allVendors as $vendor) {
            if ($this->normalizeVendorName($vendor->name) === $normalized) {
                return [
                    'match'      => $vendor,
                    'confidence' => 1.0,
                    'source'     => 'exact',
                ];
            }
        }

        // Fase 2+3: Fuzzy match sobre los vendedores del proveedor
        return $this->bestFuzzyMatch(
            $allVendors,
            $normalized,
            0.70,
            fn($v) => $this->normalizeVendorName($v->name)
        );
    }

    /**
     * Busca una medida existente que coincida con la unidad cruda.
     *
     * Aplica primero el UNIT_MAP determinista, luego busca en BD.
     *
     * @param  string $rawUnit  Unidad tal como vino del OCR/IA
     * @return array{match: Measure, confidence: float, source: string, canonical: string}|null
     */
    public function findMatchingMeasure(string $rawUnit): ?array
    {
        if (empty(trim($rawUnit))) {
            return null;
        }

        $canonical = $this->normalizeUnit($rawUnit);

        // Buscar por abreviatura canónica
        $measure = Measure::where('abbreviation', $canonical)->first();
        if ($measure) {
            return [
                'match'      => $measure,
                'confidence' => 1.0,
                'source'     => 'exact',
                'canonical'  => $canonical,
            ];
        }

        // Buscar por nombre completo (fuzzy)
        $canonicalName = $this->getUnitName($canonical);
        $measure = Measure::whereRaw('LOWER(name) = ?', [mb_strtolower($canonicalName)])->first();
        if ($measure) {
            return [
                'match'      => $measure,
                'confidence' => 0.95,
                'source'     => 'name_match',
                'canonical'  => $canonical,
            ];
        }

        // No existe — devolver null con la forma canónica para crear
        return null;
    }

    /**
     * Busca un producto existente que coincida con el nombre crudo.
     *
     * Estrategia de 3 fases igual que proveedor:
     * 1. Match exacto por normalized_name
     * 2. Match por tokens clave
     * 3. Fuzzy match sobre candidatos
     *
     * @param  string $rawName  Nombre del producto (del OCR/IA)
     * @return array{match: Product, confidence: float, source: string}|null
     */
    public function findMatchingProduct(string $rawName): ?array
    {
        if (empty(trim($rawName))) {
            return null;
        }

        $normalized = $this->normalizeText($rawName);

        // Fase 1: Match exacto por índice
        $product = Product::where('normalized_name', $normalized)->first();
        if ($product) {
            return [
                'match'      => $product,
                'confidence' => 1.0,
                'source'     => 'exact',
            ];
        }

        // Fase 2: Match por tokens clave
        $tokens = $this->extractKeyTokens($normalized);
        if (empty($tokens)) {
            return null;
        }

        $query = Product::query();
        foreach ($tokens as $token) {
            $query->where('normalized_name', 'LIKE', "%{$token}%");
        }
        $candidates = $query->limit(30)->get();

        // Fase 3: Fuzzy sobre candidatos
        return $this->bestFuzzyMatch(
            $candidates,
            $normalized,
            0.75,
            fn($p) => $p->normalized_name ?? $this->normalizeText($p->canonical_name)
        );
    }

    /**
     * Normaliza todos los ítems de una cotización parseada.
     *
     * Aplica normalización de Capa 1 a cada ítem:
     * - Unidad de medida → forma canónica
     * - Nombre del producto → limpieza básica de texto
     * - Categoría → limpieza básica
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

            // Limpiar nombre: TODO EN MAYÚSCULAS
            if (!empty($item['name'])) {
                $item['name'] = mb_strtoupper(trim(preg_replace('/\s+/', ' ', $item['name'])));
            }

            // Limpiar categoría: Mayúscula al inicio de cada palabra
            if (!empty($item['category'])) {
                $item['category'] = $this->normalizeTitleCase($item['category']);
            }

            return $item;
        }, $items);
    }

    /**
     * Normaliza un texto a Title Case (Mayúscula al inicio de cada palabra).
     *
     * @param  string $text
     * @return string
     */
    public function normalizeTitleCase(string $text): string
    {
        return mb_convert_case(trim(preg_replace('/\s+/', ' ', $text)), MB_CASE_TITLE, "UTF-8");
    }

    /* ═══════════════════════════════════════════════════
     *  UTILIDADES INTERNAS
     * ═══════════════════════════════════════════════════ */

    /**
     * Extrae tokens significativos de un texto normalizado.
     *
     * Filtra stopwords y palabras cortas para quedarse con
     * los tokens que realmente identifican la entidad.
     *
     * @param  string $normalized  Texto ya normalizado
     * @return array<string>       Tokens significativos (mín 3 chars)
     */
    private function extractKeyTokens(string $normalized): array
    {
        $stopwords = ['de', 'la', 'el', 'los', 'las', 'del', 'y', 'en', 'para', 'con', 'sin', 'por'];
        $tokens = explode(' ', $normalized);

        return array_values(array_filter(
            $tokens,
            fn(string $t) => mb_strlen($t) >= 3 && !in_array($t, $stopwords)
        ));
    }

    /**
     * Encuentra el mejor match fuzzy en una colección de candidatos.
     *
     * Método reutilizable por todos los findMatching* para evitar
     * duplicar la lógica de selección del mejor candidato.
     *
     * @param  iterable  $candidates   Colección de modelos candidatos
     * @param  string    $normalized   Texto normalizado a comparar
     * @param  float     $threshold    Umbral mínimo de similitud (0.0 - 1.0)
     * @param  callable|null $extractor Función para extraer el nombre normalizado del modelo
     * @return array{match: mixed, confidence: float, source: string}|null
     */
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
                'match'      => $bestMatch,
                'confidence' => round($bestScore, 2),
                'source'     => 'fuzzy_match',
            ];
        }

        return null;
    }

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
