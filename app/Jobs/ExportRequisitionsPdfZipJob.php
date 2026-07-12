<?php

namespace App\Jobs;

use App\Models\Requisition;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\ExportCompleted;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ExportRequisitionsPdfZipJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 300; // 5 minutos máximo

    public function __construct(
        public int $userId,
        public array $requisitionIds
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (!$user) return;

        // Recuperar configuraciones globales una sola vez para no consultar la BD en cada ciclo
        $logoData = \App\Support\StorageResolver::getAsDataUri(Setting::get('company_logo'));

        if (!$logoData) {
            $logoPath = public_path('images/logo_muulsinik.svg');
            if (file_exists($logoPath)) {
                $logoData = 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($logoPath));
            }
        }

        $company = [
            'name' => Setting::get('company_name', 'Constructora Muulsinik'),
            'rfc' => Setting::get('company_rfc', ''),
            'address' => Setting::get('company_address', ''),
            'phone' => Setting::get('company_phone', ''),
            'email' => Setting::get('company_email', ''),
        ];

        $currency = [
            'symbol' => Setting::get('currency_symbol', '$'),
            'position' => Setting::get('currency_position', 'before'),
            'decimals' => Setting::get('decimal_places', 2),
        ];

        $reqPrefix = Setting::get('req_prefix', 'REQ-');

        $zipFileName = 'Requisiciones_Export_' . now()->format('Ymd_His') . '.zip';
        $zipFilePath = storage_path('app/public/exports/' . $zipFileName);

        // Asegurar que el directorio exista
        if (!file_exists(dirname($zipFilePath))) {
            mkdir(dirname($zipFilePath), 0755, true);
        }

        try {
        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            
            $requisitions = Requisition::with([
                'project', 'vendor.supplier', 'creator', 'approver', 
                'items.product', 'items.measure', 'items.supplier'
            ])->whereIn('id', $this->requisitionIds)->get();

            foreach ($requisitions as $requisition) {
                // Renderizar PDF
                $pdf = Pdf::loadView('pdf.requisition', compact('requisition', 'logoData', 'company', 'currency'))
                    ->setPaper('letter', 'portrait')
                    ->setOption(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);

                $pdfFilename = $reqPrefix . ($requisition->number ?? $requisition->id) . '.pdf';
                
                // Agregar al ZIP
                $zip->addFromString($pdfFilename, $pdf->output());
            }

            $zip->close();

            // Verificar que el ZIP no esté vacío antes de subirlo
            if (! file_exists($zipFilePath) || filesize($zipFilePath) === 0) {
                return;
            }

            // Subir el ZIP al disco correcto y recordar en cuál tuvo éxito para construir
            // la URL de descarga con el disco explícito. Esto evita que file.preview tenga
            // que adivinar el disco haciendo múltiples peticiones a S3 que pueden timeout en Railway.
            $uploadedDisk = null;

            // 1. Intentar S3/Tigris primero (entornos cloud como Railway)
            $s3Bucket = config('filesystems.disks.s3.bucket') ?: env('AWS_BUCKET') ?: env('AWS_S3_BUCKET_NAME');
            if (!empty($s3Bucket)) {
                try {
                    $streamS3 = @fopen($zipFilePath, 'r');
                    if ($streamS3) {
                        Storage::disk('s3')->putStream('exports/' . $zipFileName, $streamS3);
                        if (is_resource($streamS3)) fclose($streamS3);
                        $uploadedDisk = 's3';
                    }
                } catch (\Throwable $eS3) {
                    // Continuar al fallback local si S3 falla
                }
            }

            // 2. Fallback: disco público local del worker actual
            if ($uploadedDisk === null) {
                try {
                    $streamPub = @fopen($zipFilePath, 'r');
                    if ($streamPub) {
                        Storage::disk('public')->putStream('exports/' . $zipFileName, $streamPub);
                        if (is_resource($streamPub)) fclose($streamPub);
                        $uploadedDisk = 'public';
                    }
                } catch (\Throwable $ePub) {
                    // Sin disco disponible — notificar igualmente con best-effort
                }
            }

            // Notificar al usuario con la ruta dinámica de descarga (file.preview) pasando el disco
            // explícito donde se subió el ZIP, evitando que streamResponse() haga múltiples
            // peticiones exists() a S3 que pueden resultar en timeout o 404 en Railway.
            $downloadParams = ['path' => 'exports/' . $zipFileName, 'download' => 1];
            if ($uploadedDisk !== null) {
                $downloadParams['disk'] = $uploadedDisk;
            }
            $downloadUrl = route('file.preview', $downloadParams);
            $user->notify(new ExportCompleted($zipFileName, $downloadUrl, 'Tus requisiciones en formato PDF están listas para descargar.'));
        }
        } finally {
            // Eliminar el ZIP temporal del disco efímero del worker para no acumular archivos en Railway
            if (file_exists($zipFilePath)) {
                @unlink($zipFilePath);
            }
        }
    }
}
