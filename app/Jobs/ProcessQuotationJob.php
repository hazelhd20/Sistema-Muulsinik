<?php

namespace App\Jobs;

use App\Models\Quotation;
use App\Services\DocumentParsers\DocumentParserFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * RF-REQ-04 — Procesamiento asíncrono de cotizaciones.
 *
 * Este Job se despacha cuando el archivo requiere OCR (imágenes, PDFs escaneados).
 * Para archivos síncronos (XLSX, PDF digital) el procesamiento ocurre inline.
 *
 * Actualiza el registro Quotation con el estado, texto extraído y datos parseados.
 */
class ProcessQuotationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(
        private readonly int $quotationId,
    ) {}

    public function handle(DocumentParserFactory $factory): void
    {
        $quotation = Quotation::findOrFail($this->quotationId);

        // Marcar como en procesamiento
        $quotation->update(['status' => 'processing']);

        try {
            $filePath  = Storage::disk('local')->path($quotation->file_path);
            $extension = pathinfo($quotation->original_filename, PATHINFO_EXTENSION);
            $mimeType  = $quotation->file_type ?? mime_content_type($filePath);

            // Resolver parser (la Factory no importa aquí si es async o no,
            // ya estamos dentro del Job)
            $resolution = $factory->resolve($filePath, $mimeType, $extension);
            $parser     = $resolution['parser'];

            // Ejecutar extracción
            $result = $parser->parse($filePath);

            // Guardar resultados en el registro
            $quotation->update([
                'status'       => 'completed',
                'raw_text'     => $result['raw_text'] ?? '',
                'parsed_data'  => $result,
                'processed_at' => now(),
            ]);

            Log::info("Quotation #{$this->quotationId} procesada exitosamente.", [
                'items_found' => count($result['items'] ?? []),
            ]);

        } catch (\Throwable $e) {
            $quotation->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error("Error al procesar cotización #{$this->quotationId}: {$e->getMessage()}");

            throw $e;
        }
    }
}
