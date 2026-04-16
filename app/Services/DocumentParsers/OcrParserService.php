<?php

namespace App\Services\DocumentParsers;

use Illuminate\Support\Str;
use thiagoalessio\TesseractOCR\TesseractOCR;

/**
 * RF-REQ-01 — Aplica OCR (Tesseract) a imágenes y PDFs escaneados.
 * Extrae texto bruto e intenta aplicar heurísticas de regex
 * para identificar productos, cantidades y precios.
 */
class OcrParserService implements ParserInterface
{
    /** {@inheritdoc} */
    public function parse(string $filePath): array
    {
        $rawText = $this->runOcr($filePath);

        return $this->structureFromText($rawText);
    }

    /**
     * Ejecuta Tesseract sobre el archivo y devuelve el texto reconocido.
     */
    private function runOcr(string $filePath): string
    {
        $ocr = new TesseractOCR($filePath);

        // Configurar la ruta al ejecutable si está definida
        $tesseractPath = config('services.tesseract.path', env('TESSERACT_PATH'));
        if ($tesseractPath && file_exists($tesseractPath)) {
            $ocr->executable($tesseractPath);
        }

        // Idioma: español por defecto, con fallback a inglés
        $lang = config('services.tesseract.lang', env('TESSERACT_LANG', 'spa'));
        $ocr->lang($lang);

        // PSM 6: asumir un bloque de texto uniforme (bueno para tablas)
        $ocr->psm(6);

        return $ocr->run();
    }

    /**
     * Estructura la salida OCR en campos identificables.
     * Misma lógica heurística que PdfTextParserService para consistencia.
     */
    private function structureFromText(string $rawText): array
    {
        $lines    = array_filter(array_map('trim', explode("\n", $rawText)));
        $supplier = null;
        $store    = null;
        $items    = [];

        foreach ($lines as $line) {
            // Patrón: "Nombre   cantidad   unidad   precio"
            if (preg_match('/^(.+?)\s+(\d+[\.,]?\d*)\s+(pza|pz|kg|m|m2|m3|lt|bulto|rollo|pieza|metro|litro|caja|und|paquete)\b[.\s]*[\$]?\s*(\d[\d,]*\.?\d*)/iu', $line, $m)) {
                $items[] = [
                    'name'       => trim($m[1]),
                    'quantity'   => (float) str_replace(',', '', $m[2]),
                    'unit'       => strtolower(trim($m[3])),
                    'unit_price' => (float) str_replace(',', '', $m[4]),
                ];
            }
        }

        // Heurística: primera línea razonable como proveedor
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
