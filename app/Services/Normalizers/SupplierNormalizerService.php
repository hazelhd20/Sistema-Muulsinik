<?php
namespace App\Services\Normalizers;

use App\Models\Supplier;
use App\Models\Vendor;

class SupplierNormalizerService
{
    public function __construct(private TextNormalizerService $text, private FuzzyMatcherService $fuzzy) {}

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

public function normalizeSupplierName(string $rawName): string
    {
        $text = mb_strtolower(trim($rawName));

        // Quitar sufijos legales (del más largo al más corto para evitar matcheos parciales)
        foreach (self::LEGAL_SUFFIXES as $suffix) {
            $text = rtrim(preg_replace('/'.preg_quote($suffix, '/').'\s*$/i', '', $text));
        }

        // Quitar prefijos comerciales genéricos que no aportan identidad
        $normalized = $this->text->normalizeText($text);
        foreach (self::BUSINESS_PREFIXES as $prefix) {
            if (str_starts_with($normalized, $prefix.' ')) {
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

public function normalizeVendorName(string $rawName): string
    {
        return $this->text->normalizeText($rawName);
    }

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
                'match' => $supplier,
                'confidence' => 1.0,
                'source' => 'exact',
            ];
        }

        // Fase 2: Match por tokens clave — más robusto que prefijo
        $tokens = $this->fuzzy->extractKeyTokens($normalized);
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
            $longestToken = collect($tokens)->sortByDesc(fn ($t) => mb_strlen($t))->first();
            $candidates = Supplier::where('normalized_name', 'LIKE', "%{$longestToken}%")
                ->limit(30)
                ->get();
        }

        // Fase 3: Fuzzy match sobre candidatos filtrados
        return $this->fuzzy->bestFuzzyMatch($candidates, $normalized, 0.70);
    }

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
                    'match' => $vendor,
                    'confidence' => 1.0,
                    'source' => 'exact',
                ];
            }
        }

        // Fase 2+3: Fuzzy match sobre los vendedores del proveedor
        return $this->fuzzy->bestFuzzyMatch(
            $allVendors,
            $normalized,
            0.70,
            fn ($v) => $this->normalizeVendorName($v->name)
        );
    }
}
