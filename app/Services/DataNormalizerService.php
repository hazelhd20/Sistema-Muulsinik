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

    /**
     * Prefijos comerciales genéricos que no aportan identidad.
     * Se eliminan SOLO durante la normalización para matching,
     * para que "Grupo Boxito" normalice a "boxito" y matchee con "Boxito".
     */
    private const BUSINESS_PREFIXES = [
        'grupo',
        'corporativo',
        'comercializadora',
        'distribuidora',
        'materiales',
        'proveedora',
        'servicios',
        'industrias',
        'constructora',
        'ferreteria',
        'empresa',
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
     * Prioridad de resolución:
     * 1. Mapa estático UNIT_NAMES (fuente más confiable)
     * 2. Hint proporcionado por la IA ($aiHint) — útil para unidades nuevas
     * 3. Fallback: ucfirst de la abreviatura
     *
     * @param  string      $canonicalUnit  Unidad canónica (e.g., "pza", "m2", "jg")
     * @param  string|null $aiHint         Nombre sugerido por la IA (e.g., "Juego")
     * @return string                      Nombre completo (e.g., "Pieza", "Juego")
     */
    public function getUnitName(string $canonicalUnit, ?string $aiHint = null): string
    {
        // 1. Mapa estático: fuente determinista más confiable
        if (isset(self::UNIT_NAMES[$canonicalUnit])) {
            return self::UNIT_NAMES[$canonicalUnit];
        }

        // 2. Hint de la IA: cubre unidades nuevas no mapeadas (jg → Juego, bls → Bolsa)
        if (!empty($aiHint)) {
            return mb_convert_case(trim($aiHint), MB_CASE_TITLE, 'UTF-8');
        }

        // 3. Fallback: capitalizar la abreviatura
        return ucfirst($canonicalUnit);
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

        // Quitar prefijos comerciales genéricos que no aportan identidad
        $normalized = $this->normalizeText($text);
        foreach (self::BUSINESS_PREFIXES as $prefix) {
            if (str_starts_with($normalized, $prefix . ' ')) {
                $stripped = trim(substr($normalized, strlen($prefix) + 1));
                // Solo quitar si queda algo sustancial (≥ 3 chars)
                if (mb_strlen($stripped) >= 3) {
                    $normalized = $stripped;
                }
                break;
            }
        }

        return $normalized;
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
     * Estrategia de 4 fases (en orden de prioridad):
     * 1. Match exacto por normalized_name (con y sin códigos SKU)
     * 2. Match por nombre canónico limpio (sin códigos) para absorber
     *    inconsistencias de la IA al eliminar/conservar códigos
     * 3. Match por tokens clave significativos
     * 4. Fuzzy match ponderado (compara AMBAS versiones: con y sin códigos)
     *
     * Principio: la IA es inconsistente limpiando códigos, así que
     * nuestro matching siempre normaliza ambos lados de la comparación.
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
        $cleanName  = $this->stripProductCodes($normalized);

        // Fase 1a: Match exacto directo (nombre tal cual vs BD)
        $product = Product::where('normalized_name', $normalized)->first();
        if ($product) {
            return [
                'match'      => $product,
                'confidence' => 1.0,
                'source'     => 'exact',
            ];
        }

        // Fase 1b: Match exacto con nombre limpio (sin códigos) vs BD
        //          Cubre el caso donde la IA limpió el código pero en BD está con código, o viceversa
        if ($cleanName !== $normalized) {
            $product = Product::where('normalized_name', $cleanName)->first();
            if ($product) {
                return [
                    'match'      => $product,
                    'confidence' => 0.98,
                    'source'     => 'exact_cleaned',
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
                $candidate->normalized_name ?? $this->normalizeText($candidate->canonical_name)
            );
            if ($candidateClean === $cleanName) {
                return [
                    'match'      => $candidate,
                    'confidence' => 0.95,
                    'source'     => 'exact_stripped',
                ];
            }
        }

        // Fase 3: Match por tokens clave (del nombre limpio para mejor precisión)
        $tokens = $this->extractKeyTokens($cleanName);
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
            $topTokens = collect($tokens)->sortByDesc(fn($t) => mb_strlen($t))->take(2)->values();
            $query = Product::query();
            foreach ($topTokens as $token) {
                $query->where('normalized_name', 'LIKE', "%{$token}%");
            }
            $candidates = $query->limit(30)->get();
        }

        // Fase 4: Fuzzy ponderado — compara la versión LIMPIA contra la BD LIMPIA
        //         Esto absorbe variaciones de la IA al conservar/quitar códigos
        return $this->bestFuzzyMatch(
            $candidates,
            $cleanName,
            0.70,
            fn($p) => $this->stripProductCodes(
                $p->normalized_name ?? $this->normalizeText($p->canonical_name)
            )
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

            // Propagar unit_name de la IA para unidades nuevas no mapeadas
            // Se preserva tal cual para usarse como hint en getUnitName()
            if (!empty($item['unit_name'])) {
                $item['unit_name'] = mb_convert_case(trim($item['unit_name']), MB_CASE_TITLE, 'UTF-8');
            }

            // Limpiar nombre: normalización determinista de producto
            // Esto absorbe las inconsistencias de la IA al quitar/dejar códigos SKU
            if (!empty($item['name'])) {
                $item['name'] = $this->normalizeProductName($item['name']);
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
        $text = trim(preg_replace('/\s+/', ' ', $text));
        $words = explode(' ', $text);

        $lowercase = ['de', 'del', 'la', 'las', 'el', 'los', 'y', 'e', 'en', 'con', 'por', 'para', 'a', 'al', 'o', 'u'];

        $result = [];
        foreach ($words as $i => $word) {
            $lower = mb_strtolower($word, 'UTF-8');
            // Primera palabra siempre en mayúscula; partículas en minúscula salvo al inicio
            if ($i === 0 || !in_array($lower, $lowercase)) {
                $result[] = mb_strtoupper(mb_substr($word, 0, 1, 'UTF-8'), 'UTF-8')
                    . mb_strtolower(mb_substr($word, 1, null, 'UTF-8'), 'UTF-8');
            } else {
                $result[] = $lower;
            }
        }

        return implode(' ', $result);
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
     * Estrategia de 3 señales combinadas:
     * 1. Similitud por caracteres (similar_text)
     * 2. Coeficiente Jaccard de tokens significativos
     * 3. Bonus por contención (un nombre dentro de otro)
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

        // Señal 1: Similitud base por caracteres
        similar_text($a, $b, $charPercent);
        $charScore = $charPercent / 100;

        // Señal 2: Tokens coincidentes (Jaccard + subset bonus)
        $tokensA = array_filter(explode(' ', $a), fn (string $w) => mb_strlen($w) >= 3);
        $tokensB = array_filter(explode(' ', $b), fn (string $w) => mb_strlen($w) >= 3);

        $tokenFinal = 0.0;
        if (!empty($tokensA) && !empty($tokensB)) {
            $intersection = count(array_intersect($tokensA, $tokensB));
            $union        = count(array_unique(array_merge($tokensA, $tokensB)));
            $jaccard      = $union > 0 ? $intersection / $union : 0;

            // Subset bonus: si TODOS los tokens del más corto están en el más largo,
            // el score mínimo es alto — indica que uno es versión abreviada del otro.
            // Ej: "leticia dzul" tiene ["leticia","dzul"], ambos en ["leticia","alejandra","dzul","uh"]
            $shorter = count($tokensA) <= count($tokensB) ? $tokensA : $tokensB;
            $longer  = count($tokensA) >  count($tokensB) ? $tokensA : $tokensB;
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
        $longer  = mb_strlen($a) >  mb_strlen($b) ? $a : $b;
        if (mb_strlen($shorter) >= 3 && str_contains($longer, $shorter)) {
            // Un nombre es subcadena completa del otro → match muy probable
            $containmentBonus = 0.85;
        }

        // Combinación: tomar el mejor indicador entre las 3 señales
        // Token analysis y containment son más confiables que character-level
        if (!empty($tokensA) && !empty($tokensB)) {
            $combined = ($tokenFinal * 0.55) + ($charScore * 0.45);
            return max($combined, $containmentBonus);
        }

        return max($charScore, $containmentBonus);
    }

    /**
     * Normaliza un nombre de producto de forma DETERMINISTA.
     *
     * Estrategia: proteger primero las medidas/especificaciones técnicas,
     * luego eliminar códigos SKU. Esto garantiza que sin importar
     * si la IA quitó o dejó los códigos, el resultado siempre es el mismo.
     *
     * Ejemplos:
     *   "M-20384 Block 15x20x40"     → "BLOCK 15X20X40"
     *   "Cemento Gris 50kg CCA-001"  → "CEMENTO GRIS 50KG"
     *   "Tubo PVC 100mm #SKU-77"     → "TUBO PVC 100MM"
     *   "Varilla 3/8 x 12m VC-38"    → "VARILLA 3/8 X 12M"
     *   "BLOCK 15X20X40"             → "BLOCK 15X20X40" (sin cambio)
     *
     * @param  string $rawName  Nombre crudo del producto
     * @return string           Nombre limpio, en MAYÚSCULAS, sin códigos SKU
     */
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

    /**
     * Limpia códigos SKU/internos de un nombre de producto.
     *
     * Estrategia en 2 fases:
     * 1. PROTEGER tokens que son medidas/especificaciones técnicas
     *    (dimensiones, pesos, calibres, diámetros, largos)
     * 2. ELIMINAR tokens que son códigos internos del proveedor
     *    (alfanuméricos con formato de SKU como "M-20384", "CCA-001", "#4521")
     *
     * Regla de oro: en caso de duda, CONSERVAR el token.
     * Es mejor dejar un código en el nombre que eliminar una medida real.
     *
     * @param  string $name  Nombre del producto (ya en mayúsculas)
     * @return string        Nombre limpio de códigos
     */
    private function stripProductCodes(string $name): string
    {
        // ═══════════════════════════════════════════
        //  PATRONES DE MEDIDAS/ESPECIFICACIONES (PROTEGER)
        // ═══════════════════════════════════════════
        //
        // Estos patrones representan medidas técnicas comunes en materiales de construcción.
        // Se deben CONSERVAR siempre, nunca eliminar.
        //
        // Patrones protegidos:
        //   Dimensiones:  15X20X40, 4X8, 10X10
        //   Fracciones:   3/8, 1/2, 3/4, 1/4
        //   Con unidad:   50KG, 100MM, 19LT, 12M, 4OZ, 6", 1/2"
        //   Calibres:     CAL 14, CAL. 12, CALIBRE 10
        //   Diámetros:    DIAM 4, DIAM. 100, 4 PULG, 4"
        //   Pulgadas:     1/2", 3/8", 6"

        $measurePattern = '/'
            . '\d+X\d+(?:X\d+)?'           // Dimensiones: 15X20X40, 4X8
            . '|\d+\/\d+(?:\s*(?:"|PULG))?' // Fracciones con/sin pulgadas: 3/8, 1/2", 3/4 PULG
            . '|\d+(?:\.\d+)?\s*(?:KG|MM|CM|LT|LTS|M[23]?|OZ|GAL|PULG|PLG|TON|GR|ML|\")'  // Número+unidad: 50KG, 100MM
            . '|CAL\.?\s*\d+'               // Calibre: CAL 14, CAL. 12
            . '|CALIBRE\s*\d+'              // CALIBRE 10
            . '|DIAM\.?\s*\d+'              // Diámetro: DIAM 4, DIAM. 100
            . '|NO\.?\s*\d+'               // Número: NO. 5, NO 3
            . '|#\d{1,3}(?!\d)'             // Calibre con #: #4, #10 (máx 3 dígitos)
            . '/i';

        // Extraer y marcar todas las medidas para protegerlas
        $protectedTokens = [];
        $nameWithPlaceholders = preg_replace_callback($measurePattern, function ($match) use (&$protectedTokens) {
            $index = count($protectedTokens);
            $protectedTokens[] = $match[0];
            return "__MEASURE_{$index}__";
        }, $name);

        // ═══════════════════════════════════════════
        //  PATRONES DE CÓDIGOS SKU (ELIMINAR)
        // ═══════════════════════════════════════════
        //
        // Formatos típicos de códigos internos de proveedores mexicanos:
        //   Prefijo-Número:   M-20384, CCA-001, SKU-4892, VC-38
        //   Hash+Número:      #4521, #SKU-77  (pero #4 se protegió arriba como calibre)
        //   Código puro:      Alfanumérico con mezcla letra+dígito al inicio/final: AB123, 123AB
        //   Código entre ():  (COD-123), (REF: ABC)

        $cleaned = $nameWithPlaceholders;

        // Quitar contenido entre paréntesis que parece info suplementaria
        // Ej: "PEGAMENTO PVC (4 OZ) TRANSPARENTE" → ya protegido por medidas
        // Solo eliminar paréntesis que contengan códigos, no medidas
        $cleaned = preg_replace_callback('/\s*\(([^)]*)\)\s*/', function ($match) {
            $content = trim($match[1]);
            // Si el contenido del paréntesis tiene un placeholder de medida, conservarlo
            if (str_contains($content, '__MEASURE_')) {
                return ' ' . $content . ' ';
            }
            // Si parece una medida (tiene número + unidad), conservar
            if (preg_match('/\d+\s*(?:KG|MM|CM|LT|OZ|M|GAL|PULG)/i', $content)) {
                return ' ' . $content . ' ';
            }
            // Eliminar — probablemente es código o info irrelevante
            return ' ';
        }, $cleaned);

        // Quitar códigos con formato PREFIJO-NÚMERO (al inicio o al final)
        // Ej: M-20384, CCA-001, SKU-4892, VC-38, REF-123
        // PERO no quitar si parece nombre compuesto (ej: "POLVO-GRIS" tiene letras en ambos lados del guión)
        $cleaned = preg_replace('/(?:^|\s)[A-Z]{1,5}-\d{2,}(?:\s|$)/i', ' ', $cleaned);
        $cleaned = preg_replace('/(?:^|\s)\d{2,}-[A-Z]{1,5}(?:\s|$)/i', ' ', $cleaned);

        // Quitar SKU con prefijo hashtag (que tengan letras intermedias o 3+ dígitos: #SKU-77, #4521)
        // Los calibres cortos (#4, #10) ya están protegidos como __MEASURE_N__
        $cleaned = preg_replace('/#[A-Z]+-?\d+/i', '', $cleaned);    // #SKU-77, #REF123
        $cleaned = preg_replace('/#\d{4,}/i', '', $cleaned);          // #45210

        // Quitar prefijos de código explícitos (SKU, COD, REF, ART, CLAVE)
        $cleaned = preg_replace('/\b(?:SKU|COD|REF|ART|CLAVE)[:\s\-]*[A-Z0-9\-]{2,}\b/i', '', $cleaned);

        // Limpiar hashtags huérfanos que quedaron tras eliminar códigos
        $cleaned = preg_replace('/\s*#\s*/', ' ', $cleaned);

        // Quitar tokens alfanuméricos sueltos que parecen códigos (mezcla letra+dígito)
        // Solo al INICIO o FINAL del nombre, no en medio
        // Ej: "AB1234 CEMENTO GRIS" → "CEMENTO GRIS"
        // Ej: "CEMENTO GRIS XY789" → "CEMENTO GRIS"
        // PERO conservar si es solo letras (nombre) o solo números precedidos por unidad protegida
        $cleaned = preg_replace('/^[A-Z]{1,3}\d{3,}\s+/i', '', $cleaned);  // Inicio: AB1234
        $cleaned = preg_replace('/\s+[A-Z]{1,3}\d{3,}$/i', '', $cleaned);  // Final: XY789
        $cleaned = preg_replace('/^\d{3,}[A-Z]{1,3}\s+/i', '', $cleaned);  // Inicio: 1234AB
        $cleaned = preg_replace('/\s+\d{3,}[A-Z]{1,3}$/i', '', $cleaned);  // Final: 789XY

        // ═══════════════════════════════════════════
        //  RESTAURAR MEDIDAS PROTEGIDAS
        // ═══════════════════════════════════════════
        foreach ($protectedTokens as $index => $original) {
            $cleaned = str_replace("__MEASURE_{$index}__", $original, $cleaned);
        }

        $cleaned = trim(preg_replace('/\s+/', ' ', $cleaned));

        // Protección final: si la limpieza dejó el nombre vacío o muy corto,
        // devolver el original — es mejor un nombre con código que sin nombre
        if (mb_strlen($cleaned) < 3) {
            return trim(preg_replace('/\s+/', ' ', $name));
        }

        return $cleaned;
    }
}
