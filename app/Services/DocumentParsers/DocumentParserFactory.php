<?php

namespace App\Services\DocumentParsers;

use RuntimeException;

/**
 * RF-REQ-02 — Detecta automáticamente el tipo de procesamiento
 * requerido según formato y contenido del archivo cargado.
 *
 * Devuelve la instancia del parser correspondiente
 * sin que el usuario necesite seleccionarlo manualmente.
 *
 * Flujo por formato:
 * - Imágenes (jpg, png, webp)  → VisionParser (Gemini Vision multimodal)
 * - PDFs escaneados            → VisionParser (Gemini lee el PDF directamente)
 * - PDFs con texto digital     → PdfTextParser (smalot extrae texto → Gemini texto)
 * - Hojas de cálculo (xlsx)    → SpreadsheetParser (PhpSpreadsheet → Gemini texto)
 */
class DocumentParserFactory
{
    public function __construct(
        private readonly PdfTextParserService $pdfParser,
        private readonly VisionParserService $visionParser,
        private readonly SpreadsheetParserService $spreadsheetParser,
    ) {}

    /**
     * Resuelve el parser adecuado según la extensión / MIME del archivo.
     *
     * @return array{parser: ParserInterface, async: bool}
     */
    public function resolve(string $filePath, string $mimeType, string $extension): array
    {
        $extension = strtolower($extension);

        // XLSX/XLS → lectura directa de celdas
        if ($extension === 'xlsx' || $extension === 'xls') {
            return ['parser' => $this->spreadsheetParser, 'async' => true];
        }

        // PDF → detectar si tiene texto digital o es escaneado
        if ($extension === 'pdf') {
            $hasText = $this->pdfParser->hasExtractableText($filePath);

            // PDFs con texto digital: extraer texto y enviar a Gemini como texto
            // PDFs escaneados: enviar directamente a Gemini Vision como Blob
            return $hasText
                ? ['parser' => $this->pdfParser, 'async' => true]
                : ['parser' => $this->visionParser, 'async' => true];
        }

        // Imágenes → Gemini Vision directamente (asíncrono)
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
            return ['parser' => $this->visionParser, 'async' => true];
        }

        throw new RuntimeException("Formato de archivo no soportado: .{$extension}");
    }
}
