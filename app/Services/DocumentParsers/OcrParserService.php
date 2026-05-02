<?php

namespace App\Services\DocumentParsers;

use App\Services\AI\GeminiStructurerService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use thiagoalessio\TesseractOCR\TesseractOCR;

/**
 * RF-REQ-01 — Aplica OCR (Tesseract) a imágenes y PDFs escaneados.
 * Extrae texto bruto y delega a Gemini AI para estructuración inteligente.
 * Si Gemini no está disponible, aplica heurísticas de regex como fallback.
 */
class OcrParserService implements ParserInterface
{
    public function __construct(
        private readonly GeminiStructurerService $gemini,
    ) {}

    /** {@inheritdoc} */
    public function parse(string $filePath): array
    {
        // Estrategia Vision-First: enviar la imagen directamente a Gemini
        // en vez de pasar por Tesseract OCR, que produce texto ruidoso en
        // documentos con tablas complejas, múltiples columnas o baja resolución.
        $visionResult = $this->tryVisionExtraction($filePath);

        if ($visionResult !== null) {
            return $visionResult;
        }

        // Fallback: pipeline clásico Tesseract → Gemini texto → regex
        Log::info('OCR Parser: Vision no disponible o falló, usando pipeline Tesseract.', [
            'path' => $filePath,
        ]);

        return $this->parseWithTesseract($filePath);
    }

    /**
     * Intenta extraer datos directamente de la imagen vía Gemini Vision.
     * Retorna null si Vision no está disponible o falla.
     */
    private function tryVisionExtraction(string $filePath): ?array
    {
        return $this->gemini->structureFromImage($filePath);
    }

    /**
     * Pipeline clásico: Tesseract OCR → Gemini AI texto → regex fallback.
     * Se usa solo cuando Gemini Vision no está disponible o falla.
     */
    private function parseWithTesseract(string $filePath): array
    {
        $rawText = $this->runOcr($filePath);

        // Intentar estructuración inteligente con Gemini AI (texto)
        $aiResult = $this->gemini->structureRawText($rawText);

        if ($aiResult !== null) {
            $aiResult['raw_text'] = $rawText;
            return $aiResult;
        }

        // Fallback: heurísticas de regex
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

        // Configurar el directorio de datos de entrenamiento si existe
        $tessdataDir = storage_path('app/tessdata');
        if (file_exists($tessdataDir)) {
            $ocr->tessdataDir($tessdataDir);
        }

        // Idioma: español por defecto, con fallback a inglés
        $lang = config('services.tesseract.lang', env('TESSERACT_LANG', 'spa'));
        $ocr->lang($lang);

        // PSM 3: Segmentación automática completa (default).
        // Anteriormente estaba en 6 (bloque uniforme), lo que causaba
        // que la primera fila de la tabla fuera descartada si Tesseract
        // la interpretaba como parte de un bloque separado o del encabezado.
        $ocr->psm(3);

        // Preservar espacios entre palabras múltiples para mantener la
        // alineación de columnas, crucial para cotizaciones estructuradas
        $ocr->preserve_interword_spaces(1);

        return $ocr->run();
    }

    /**
     * Fallback: Estructura la salida OCR en campos identificables mediante regex.
     * Se usa solo cuando Gemini AI no está disponible o falla.
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
                    'name'               => trim($m[1]),
                    'quantity'           => (float) str_replace(',', '', $m[2]),
                    'unit'               => strtolower(trim($m[3])),
                    'unit_price'         => (float) str_replace(',', '', $m[4]),
                    'tax_amount'         => null,
                    'price_includes_tax' => null,
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
            'tax_info' => null,
            'items'    => $items,
            'raw_text' => $rawText,
        ];
    }
}
