<?php
namespace App\Services\Normalizers;

use App\Models\Measure;

class UnitNormalizerService
{
private const UNIT_MAP = [
        // Pieza
        'pieza' => 'pza', 'piezas' => 'pza', 'pz' => 'pza',
        'pza' => 'pza', 'pzas' => 'pza', 'pzs' => 'pza',
        'und' => 'pza', 'unidad' => 'pza', 'unidades' => 'pza',
        'u' => 'pza', 'pc' => 'pza', 'pcs' => 'pza',

        // Metro lineal
        'metro' => 'm', 'metros' => 'm', 'mt' => 'm',
        'mts' => 'm', 'ml' => 'm', 'm' => 'm',

        // Metro cuadrado
        'metro cuadrado' => 'm2', 'metros cuadrados' => 'm2',
        'm2' => 'm2', 'mt2' => 'm2',
        'mts2' => 'm2', 'm²' => 'm2',

        // Metro cúbico
        'metro cubico' => 'm3', 'metro cúbico' => 'm3',
        'metros cubicos' => 'm3', 'm3' => 'm3', 'mt3' => 'm3',
        'mts3' => 'm3', 'm³' => 'm3',

        // Kilogramo
        'kilogramo' => 'kg', 'kilogramos' => 'kg', 'kgs' => 'kg',
        'kilo' => 'kg', 'kilos' => 'kg', 'kg' => 'kg',

        // Litro
        'litro' => 'lt', 'litros' => 'lt', 'l' => 'lt',
        'lts' => 'lt', 'lt' => 'lt',

        // Bulto
        'bulto' => 'bulto', 'bultos' => 'bulto', 'bto' => 'bulto',
        'btos' => 'bulto',

        // Rollo
        'rollo' => 'rollo', 'rollos' => 'rollo', 'rll' => 'rollo',

        // Caja
        'caja' => 'caja', 'cajas' => 'caja',

        // Paquete
        'paquete' => 'paquete', 'paquetes' => 'paquete', 'paq' => 'paquete',

        // Tonelada
        'tonelada' => 'ton', 'toneladas' => 'ton', 'ton' => 'ton',
        'tons' => 'ton',

        // Cubo
        'cubo' => 'cubo', 'cubos' => 'cubo',

        // Tramo
        'tramo' => 'tramo', 'tramos' => 'tramo',

        // Galón
        'galon' => 'galon', 'galón' => 'galon', 'galones' => 'galon',
        'gal' => 'galon',

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

public function normalizeUnit(string $rawUnit): string
    {
        // Limpiar: lowercase, sin puntos, sin espacios extremos
        $clean = mb_strtolower(trim($rawUnit));
        $clean = str_replace('.', '', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);

        return self::UNIT_MAP[$clean] ?? $clean;
    }

public function getUnitName(string $canonicalUnit, ?string $aiHint = null): string
    {
        // 1. Mapa estático: fuente determinista más confiable
        if (isset(self::UNIT_NAMES[$canonicalUnit])) {
            return self::UNIT_NAMES[$canonicalUnit];
        }

        // 2. Hint de la IA: cubre unidades nuevas no mapeadas (jg → Juego, bls → Bolsa)
        if (! empty($aiHint)) {
            return mb_convert_case(trim($aiHint), MB_CASE_TITLE, 'UTF-8');
        }

        // 3. Fallback: capitalizar la abreviatura
        return ucfirst($canonicalUnit);
    }

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
                'match' => $measure,
                'confidence' => 1.0,
                'source' => 'exact',
                'canonical' => $canonical,
            ];
        }

        // Buscar por nombre completo (fuzzy)
        $canonicalName = $this->getUnitName($canonical);
        $measure = Measure::whereRaw('LOWER(name) = ?', [mb_strtolower($canonicalName)])->first();
        if ($measure) {
            return [
                'match' => $measure,
                'confidence' => 0.95,
                'source' => 'name_match',
                'canonical' => $canonical,
            ];
        }

        // No existe — devolver null con la forma canónica para crear
        return null;
    }
}
