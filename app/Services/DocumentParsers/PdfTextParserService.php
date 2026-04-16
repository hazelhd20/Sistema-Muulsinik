<?php

namespace App\Services\DocumentParsers;

use Smalot\PdfParser\Parser;
use Illuminate\Support\Str;

/**
 * RF-REQ-01 — Extrae texto de PDFs con contenido digital seleccionable.
 * Usa smalot/pdfparser para lectura directa sin dependencias externas.
 */
class PdfTextParserService implements ParserInterface
{
    /**
     * Determina si el PDF tiene texto seleccionable (no escaneado).
     * Se usa en la Factory para decidir si pasa a OCR.
     */
    public function hasExtractableText(string $filePath): bool
    {
        try {
            $parser = new Parser();
            $pdf    = $parser->parseFile($filePath);
            $text   = $pdf->getText();

            // Si tiene más de 50 chars de texto real, es digital
            return Str::length(trim($text)) > 50;
        } catch (\Throwable) {
            return false;
        }
    }

    /** {@inheritdoc} */
    public function parse(string $filePath): array
    {
        $parser  = new Parser();
        $pdf     = $parser->parseFile($filePath);
        $rawText = $pdf->getText();

        return $this->structureFromText($rawText);
    }

    /**
     * Intenta extraer proveedor, tienda y líneas de productos
     * a partir del texto plano mediante heurísticas y regex.
     */
    private function structureFromText(string $rawText): array
    {
        $lines    = array_filter(array_map('trim', explode("\n", $rawText)));
        $supplier = null;
        $store    = null;
        $items    = [];

        foreach ($lines as $line) {
            // Buscar patrón de producto:  "Nombre producto   2   pza   $150.00"
            if (preg_match('/^(.+?)\s+(\d+[\.,]?\d*)\s+(pza|pz|kg|m|m2|m3|lt|bulto|rollo|pieza|metro|litro|caja|und|paquete)\b[.\s]*[\$]?\s*(\d[\d,]*\.?\d*)/iu', $line, $m)) {
                $items[] = [
                    'name'       => trim($m[1]),
                    'quantity'   => (float) str_replace(',', '', $m[2]),
                    'unit'       => strtolower(trim($m[3])),
                    'unit_price' => (float) str_replace(',', '', $m[4]),
                ];
            }
        }

        // Heurística simple: primera línea con longitud razonable podría ser el proveedor
        foreach ($lines as $line) {
            if (Str::length($line) > 3 && Str::length($line) < 80) {
                $supplier = $line;
                break;
            }
        }

        return [
            'supplier' => $supplier,
            'store'    => $store,
            'items'    => $items,
            'raw_text' => $rawText,
        ];
    }
}
