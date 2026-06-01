<?php

namespace App\Services\DocumentParsers;

use App\Services\AI\GeminiStructurerService;
use Illuminate\Support\Facades\Log;

/**
 * RF-REQ-01 — Procesa imágenes y PDFs escaneados mediante Gemini Vision.
 *
 * Envía el archivo directamente a Gemini como Blob multimodal, permitiendo
 * que la IA "vea" el documento completo (layout, columnas, tablas, números)
 * y extraiga la información con alta precisión.
 *
 * Soporta: JPG, JPEG, PNG, WebP, HEIC, HEIF, PDF (escaneados).
 */
class VisionParserService implements ParserInterface
{
    public function __construct(
        private readonly GeminiStructurerService $gemini,
    ) {}

    /** {@inheritdoc} */
    public function parse(string $filePath): array
    {
        $result = $this->gemini->structureFromFile($filePath);

        if ($result !== null) {
            return $result;
        }

        // Gemini no disponible o falló: lanzar excepción para que el Job la capture
        // y el UI muestre la opción de reintentar o continuar manualmente.
        Log::warning('Vision Parser: Gemini no pudo procesar el archivo.', [
            'path' => $filePath,
        ]);

        throw new \Exception('La IA de Gemini está experimentando alta demanda o no pudo procesar el archivo en este momento. Por favor, intenta de nuevo o continúa manualmente.');
    }
}
