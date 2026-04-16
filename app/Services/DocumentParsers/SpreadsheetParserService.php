<?php

namespace App\Services\DocumentParsers;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * RF-REQ-01 — Lee datos directamente de celdas de archivos XLSX.
 * Intenta detectar automáticamente las columnas de producto,
 * cantidad, unidad y precio unitario mediante heurísticas de encabezado.
 */
class SpreadsheetParserService implements ParserInterface
{
    /**
     * Mapeo flexible de encabezados comunes a campos del sistema.
     * Se buscan coincidencias parciales (case-insensitive).
     */
    private const HEADER_MAP = [
        'name'       => ['producto', 'material', 'descripcion', 'descripción', 'articulo', 'artículo', 'nombre', 'concepto', 'item'],
        'quantity'   => ['cantidad', 'cant', 'qty', 'pzas', 'unidades'],
        'unit'       => ['unidad', 'u.m.', 'um', 'medida', 'unit'],
        'unit_price' => ['precio', 'p.u.', 'pu', 'costo', 'unit price', 'precio unitario', 'valor'],
    ];

    /** {@inheritdoc} */
    public function parse(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray(null, true, true, true);

        if (empty($rows)) {
            return ['supplier' => null, 'store' => null, 'items' => [], 'raw_text' => ''];
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

        // Si no se detectaron encabezados, retornar todo como texto crudo
        if ($headerRow === null) {
            return [
                'supplier' => null,
                'store'    => null,
                'items'    => [],
                'raw_text' => implode("\n", $rawLines),
            ];
        }

        // Paso 2: leer las filas de datos
        $items = [];
        $dataStarted = false;

        foreach ($rows as $rowIndex => $row) {
            if ($rowIndex <= $headerRow) {
                continue;
            }

            $name = $this->getCellValue($row, $columnMap, 'name');
            if (empty($name)) {
                continue;
            }

            $items[] = [
                'name'       => $name,
                'quantity'   => $this->toFloat($this->getCellValue($row, $columnMap, 'quantity')),
                'unit'       => $this->getCellValue($row, $columnMap, 'unit') ?: 'pza',
                'unit_price' => $this->toFloat($this->getCellValue($row, $columnMap, 'unit_price')),
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
            'items'    => $items,
            'raw_text' => implode("\n", $rawLines),
        ];
    }

    /**
     * Detecta columnas de encabezado a partir de nombres conocidos.
     *
     * @return array<string, string> campo => letra_columna
     */
    private function detectHeaders(array $row): array
    {
        $map = [];
        foreach ($row as $col => $cellValue) {
            if (empty($cellValue)) {
                continue;
            }
            $normalized = mb_strtolower(trim((string) $cellValue));

            foreach (self::HEADER_MAP as $field => $keywords) {
                foreach ($keywords as $keyword) {
                    if (str_contains($normalized, $keyword)) {
                        $map[$field] = $col;
                        break 2;
                    }
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
