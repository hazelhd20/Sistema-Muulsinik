<?php

namespace App\Jobs;

use App\Models\Quotation;
use App\Notifications\QuotationProcessed;
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
            $disk = Storage::disk(config('filesystems.default'));
            $extension = pathinfo($quotation->original_filename, PATHINFO_EXTENSION);
            
            // Descargar temporalmente el archivo desde S3/Tigris/local para que los parsers (IA/Excel) puedan leerlo físicamente
            $tempPath = sys_get_temp_dir() . '/' . uniqid('quote_') . '.' . $extension;
            file_put_contents($tempPath, $disk->get($quotation->file_path));

            $mimeType = $quotation->file_type ?? mime_content_type($tempPath);

            // Resolver parser (la Factory no importa aquí si es async o no,
            // ya estamos dentro del Job)
            $resolution = $factory->resolve($tempPath, $mimeType, $extension);
            $parser = $resolution['parser'];

            // Ejecutar extracción
            $result = $parser->parse($tempPath);

            @unlink($tempPath); // Limpiar memoria temporal

            // Guardar resultados en el registro
            $quotation->update([
                'status' => 'completed',
                'raw_text' => $result['raw_text'] ?? null,
                'raw_parsed_data' => $result,
                'processed_at' => now(),
            ]);

            if ($quotation->uploader) {
                $quotation->uploader->notify(new QuotationProcessed($quotation, true));
            }

            Log::info("Quotation #{$this->quotationId} procesada exitosamente.", [
                'items_found' => count($result['items'] ?? []),
            ]);

        } catch (\Throwable $e) {
            $quotation->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            if ($quotation->uploader) {
                $quotation->uploader->notify(new QuotationProcessed($quotation, false, $e->getMessage()));
            }

            Log::error("Error al procesar cotización #{$this->quotationId}: {$e->getMessage()}");

            throw $e;
        }
    }
}
