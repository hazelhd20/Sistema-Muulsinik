<?php

namespace App\Services\DocumentParsers;

use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * RF-REQ-02 — Detecta automáticamente el tipo de procesamiento
 * requerido según formato y contenido del archivo cargado.
 *
 * Devuelve la instancia del parser correspondiente
 * sin que el usuario necesite seleccionarlo manualmente.
 */
class DocumentParserFactory
{
    public function __construct(
        private readonly PdfTextParserService $pdfParser,
        private readonly OcrParserService $ocrParser,
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

        // XLSX → lectura directa de celdas (síncrono)
        if ($extension === 'xlsx' || $extension === 'xls') {
            return ['parser' => $this->spreadsheetParser, 'async' => false];
        }

        // PDF → detectar si tiene texto digital o es escaneado
        if ($extension === 'pdf') {
            $hasText = $this->pdfParser->hasExtractableText($filePath);

            return $hasText
                ? ['parser' => $this->pdfParser, 'async' => false]
                : ['parser' => $this->ocrParser, 'async' => true];
        }

        // Imágenes → siempre OCR (asíncrono)
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
            return ['parser' => $this->ocrParser, 'async' => true];
        }

        throw new RuntimeException("Formato de archivo no soportado: .{$extension}");
    }
}
