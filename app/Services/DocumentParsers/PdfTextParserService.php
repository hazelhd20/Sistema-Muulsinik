<?php

namespace App\Services\DocumentParsers;

use App\Services\AI\GeminiStructurerService;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;

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
            $parser = new Parser;
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();

            // Si tiene más de 50 chars de texto real, es digital
            return Str::length(trim($text)) > 50;
        } catch (\Throwable) {
            return false;
        }
    }

    /** {@inheritdoc} */
    public function parse(string $filePath): array
    {
        $parser = new Parser;
        $pdf = $parser->parseFile($filePath);
        $rawText = $pdf->getText();

        // Intentar estructuración inteligente con Gemini AI
        $aiResult = $this->gemini->structureRawText($rawText);

        if ($aiResult !== null) {
            $aiResult['raw_text'] = $rawText;

            return $aiResult;
        }

        $errorMessage = $this->gemini->lastError ?? 'No se identificaron partidas legibles en el texto del PDF.';
        throw new \RuntimeException($errorMessage);
    }
}
