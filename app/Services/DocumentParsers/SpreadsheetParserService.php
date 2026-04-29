<?php

namespace App\Services\DocumentParsers;

use App\Services\AI\GeminiStructurerService;
use App\Services\DataNormalizerService;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * RF-REQ-01 — Lee datos directamente de celdas de archivos XLSX.
 * Intenta detectar automáticamente las columnas de producto,
 * cantidad, unidad y precio unitario mediante heurísticas de encabezado.
 * Aplica normalización determinista de datos vía DataNormalizerService.
 * Si no detecta headers, delega a Gemini AI como fallback.
 */
class SpreadsheetParserService implements ParserInterface
{
    /**
     * Mapeo flexible de encabezados comunes a campos del sistema.
     *
     * IMPORTANTE: El orden de los mapeos importa.
     * Los campos más específicos (como 'line_total') se evalúan antes
     * que los genéricos (como 'unit_price') para evitar que "importe neto"
     * sea capturado erróneamente como "precio unitario" por contener "importe".
     *
     * Variaciones comunes en cotizaciones mexicanas:
     * - "precio" / "p.u." → precio unitario
     * - "importe" → subtotal de línea (cantidad × precio)
     * - "impuesto" → IVA de línea
     * - "importe neto" / "total" → total con IVA por línea
     */
    private const HEADER_MAP = [
        'name'          => ['producto', 'material', 'descripcion', 'descripción', 'articulo', 'artículo', 'nombre', 'concepto', 'item'],
        'quantity'      => ['cantidad', 'cant', 'qty', 'pzas', 'unidades'],
        'unit'          => ['unidad', 'u.m.', 'um', 'medida', 'unit'],
        // Campos más específicos primero para evitar matcheos ambiguos
        'line_total'    => ['importe neto', 'total neto', 'monto total', 'total con iva', 'neto'],
        'line_subtotal' => ['importe', 'subtotal', 'sub total', 'monto'],
        'tax_amount'    => ['impuesto', 'iva', 'i.v.a.', 'i.v.a', 'tax'],
        'unit_price'    => ['precio unitario', 'p. unitario', 'p.u.', 'pu', 'precio', 'costo', 'unit price', 'costo unitario', 'valor unitario'],
    ];

    public function __construct(
        private readonly GeminiStructurerService $gemini,
        private readonly DataNormalizerService $normalizer,
    ) {}

    /** {@inheritdoc} */
    public function parse(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray(null, true, true, true);

        if (empty($rows)) {
            return ['supplier' => null, 'store' => null, 'tax_info' => null, 'items' => [], 'raw_text' => ''];
        }

        // Paso 1: encontrar la fila de encabezados
        $headerRow  = null;
        $columnMap  = [];
        $rawLines   = [];

        foreach ($rows as $rowIndex => $row) {
            $rawLines[] = implode(' | ', array_filter($row));
            $detected   = $this->detectHeaders($row);

            if (count($detected) >= 2) {
                $headerRow = $rowIndex;
                $columnMap = $detected;
                break;
            }
        }

        // Si no se detectaron encabezados, intentar con Gemini AI sobre el texto crudo
        if ($headerRow === null) {
            $rawText  = implode("\n", $rawLines);
            $aiResult = $this->gemini->structureRawText($rawText);

            if ($aiResult !== null) {
                $aiResult['raw_text'] = $rawText;
                // Normalizar unidades de los ítems extraídos por IA
                $aiResult['items'] = $this->normalizer->normalizeItems($aiResult['items'] ?? []);
                return $aiResult;
            }

            return [
                'supplier' => null,
                'store'    => null,
                'tax_info' => null,
                'items'    => [],
                'raw_text' => $rawText,
            ];
        }

        // Paso 2: leer las filas de datos
        $items = [];
        foreach ($rows as $rowIndex => $row) {
            if ($rowIndex <= $headerRow) {
                continue;
            }

            $name = $this->getCellValue($row, $columnMap, 'name');
            if (empty($name)) {
                continue;
            }

            $quantity     = $this->toFloat($this->getCellValue($row, $columnMap, 'quantity'));
            $unitPrice    = $this->toFloat($this->getCellValue($row, $columnMap, 'unit_price'));
            $lineSubtotal = $this->toFloat($this->getCellValue($row, $columnMap, 'line_subtotal'));
            $lineTotal    = $this->toFloat($this->getCellValue($row, $columnMap, 'line_total'));
            $taxAmount    = $this->toFloat($this->getCellValue($row, $columnMap, 'tax_amount'));

            // Inferir unit_price si no se detectó pero hay subtotal y cantidad
            $unitPrice = $this->inferUnitPrice($unitPrice, $quantity, $lineSubtotal, $lineTotal, $taxAmount);

            // Normalizar unidad vía DataNormalizerService
            $rawUnit = $this->getCellValue($row, $columnMap, 'unit') ?: 'pza';
            $normalizedUnit = $this->normalizer->normalizeUnit($rawUnit);

            $items[] = [
                'name'               => trim($name),
                'quantity'           => $quantity,
                'unit'               => $normalizedUnit,
                'unit_price'         => $unitPrice,
                'tax_amount'         => $taxAmount,
                'price_includes_tax' => null,
                'line_subtotal'      => $lineSubtotal,
                'line_total'         => $lineTotal,
            ];
        }

        // Intentar encontrar el proveedor en las filas previas al encabezado
        $supplier = null;
        foreach ($rows as $rowIndex => $row) {
            if ($rowIndex >= $headerRow) {
                break;
            }
            $text = trim(implode(' ', array_filter($row)));
            if (strlen($text) > 3 && strlen($text) < 100) {
                $supplier = $text;
                break;
            }
        }

        return [
            'supplier' => $supplier,
            'store'    => null,
            'tax_info' => null,
            'items'    => $items,
            'raw_text' => implode("\n", $rawLines),
        ];
    }

    /**
     * Infiere el precio unitario cuando no se detectó directamente.
     *
     * Prioridad:
     * 1. Si ya se tiene unit_price → usarlo.
     * 2. Si hay subtotal y cantidad → subtotal / cantidad.
     * 3. Si hay total, impuesto y cantidad → (total - impuesto) / cantidad.
     * 4. Si hay total y cantidad (sin impuesto) → total / cantidad.
     */
    private function inferUnitPrice(
        ?float $unitPrice,
        ?float $quantity,
        ?float $lineSubtotal,
        ?float $lineTotal,
        ?float $taxAmount,
    ): ?float {
        if ($unitPrice !== null && $unitPrice > 0) {
            return $unitPrice;
        }

        $qty = ($quantity !== null && $quantity > 0) ? $quantity : 1.0;

        if ($lineSubtotal !== null && $lineSubtotal > 0) {
            return round($lineSubtotal / $qty, 2);
        }

        if ($lineTotal !== null && $lineTotal > 0) {
            $base = ($taxAmount !== null && $taxAmount > 0)
                ? $lineTotal - $taxAmount
                : $lineTotal;

            return round($base / $qty, 2);
        }

        return null;
    }

    /**
     * Detecta columnas de encabezado a partir de nombres conocidos.
     *
     * Usa un enfoque de "longest match first": evalúa las keywords
     * más largas primero para evitar que "precio" capture la columna
     * "precio unitario" cuando ambas existen.
     *
     * @return array<string, string> campo => letra_columna
     */
    private function detectHeaders(array $row): array
    {
        $map = [];
        $assignedColumns = [];

        // Pre-normalizar todas las celdas del encabezado
        $normalizedCells = [];
        foreach ($row as $col => $cellValue) {
            if (empty($cellValue)) {
                continue;
            }
            $normalizedCells[$col] = mb_strtolower(trim((string) $cellValue));
        }

        // Construir lista plana de (campo, keyword, longitud) y ordenar por longitud descendente
        $matchCandidates = [];
        foreach (self::HEADER_MAP as $field => $keywords) {
            foreach ($keywords as $keyword) {
                $matchCandidates[] = [
                    'field'   => $field,
                    'keyword' => $keyword,
                    'length'  => mb_strlen($keyword),
                ];
            }
        }
        usort($matchCandidates, fn ($a, $b) => $b['length'] <=> $a['length']);

        // Asignar columnas: keywords más largas primero, cada columna solo se asigna una vez
        foreach ($matchCandidates as $candidate) {
            if (isset($map[$candidate['field']])) {
                continue; // Campo ya mapeado
            }

            foreach ($normalizedCells as $col => $normalized) {
                if (in_array($col, $assignedColumns, true)) {
                    continue; // Columna ya asignada a otro campo
                }

                if (str_contains($normalized, $candidate['keyword'])) {
                    $map[$candidate['field']] = $col;
                    $assignedColumns[] = $col;
                    break;
                }
            }
        }

        return $map;
    }

    private function getCellValue(array $row, array $columnMap, string $field): ?string
    {
        if (!isset($columnMap[$field])) {
            return null;
        }
        $val = $row[$columnMap[$field]] ?? null;
        return $val !== null ? trim((string) $val) : null;
    }

    private function toFloat(?string $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (float) str_replace([',', '$', ' '], ['', '', ''], $value);
    }
}
