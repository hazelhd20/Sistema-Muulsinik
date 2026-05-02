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

        // Gemini no disponible o falló: retornar estructura vacía
        // para que el usuario pueda capturar manualmente los datos.
        Log::warning('Vision Parser: Gemini no pudo procesar el archivo.', [
            'path' => $filePath,
        ]);

        return [
            'supplier' => null,
            'store'    => null,
            'tax_info' => null,
            'items'    => [],
            'raw_text' => '[No se pudo extraer texto del archivo]',
        ];
    }
}
