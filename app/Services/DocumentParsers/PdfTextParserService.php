<?php

namespace App\Services\DocumentParsers;

use App\Services\AI\GeminiStructurerService;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Str;

/**
 * RF-REQ-01 — Extrae texto de PDFs con contenido digital seleccionable.
 * Usa smalot/pdfparser para lectura directa y Gemini AI para estructuración.
 * Si Gemini no está disponible, aplica heurísticas de regex como fallback.
 */
class PdfTextParserService implements ParserInterface
{
    public function __construct(
        private readonly GeminiStructurerService $gemini,
    ) {}

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

        // Intentar estructuración inteligente con Gemini AI
        $aiResult = $this->gemini->structureRawText($rawText);

        if ($aiResult !== null) {
            $aiResult['raw_text'] = $rawText;
            return $aiResult;
        }

        // Fallback: heurísticas de regex
        return $this->structureFromText($rawText);
    }

    /**
     * Fallback: Intenta extraer proveedor, tienda y líneas de productos
     * a partir del texto plano mediante heurísticas y regex.
     * Se usa solo cuando Gemini AI no está disponible o falla.
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
                    'name'               => trim($m[1]),
                    'quantity'           => (float) str_replace(',', '', $m[2]),
                    'unit'               => strtolower(trim($m[3])),
                    'unit_price'         => (float) str_replace(',', '', $m[4]),
                    'tax_amount'         => null,
                    'price_includes_tax' => null,
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
            'tax_info' => null,
            'items'    => $items,
            'raw_text' => $rawText,
        ];
    }
}
