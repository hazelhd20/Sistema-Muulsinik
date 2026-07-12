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

        // Configurar ZIP
        $zipFileName = 'Requisiciones_Export_' . now()->format('Ymd_His') . '.zip';
        $zipFilePath = storage_path('app/public/exports/' . $zipFileName);

        // Asegurar que el directorio exista
        if (!file_exists(dirname($zipFilePath))) {
            mkdir(dirname($zipFilePath), 0755, true);
        }

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

            // Guardar en la nube de S3/Tigris (si las credenciales existen en el contenedor) y en el disco por defecto/público.
            // Esto asegura que si Railway ejecuta las colas en un contenedor separado al del servidor web (disco local efímero),
            // el archivo ZIP siempre esté sincronizado y accesible desde el bucket de S3/Tigris sin dar error 404.
            try {
                // 1. Subir a S3/Tigris por stream si está configurado en las variables de entorno o config
                if (!empty(config('filesystems.disks.s3.bucket')) || !empty(env('AWS_ACCESS_KEY_ID')) || !empty(env('AWS_BUCKET'))) {
                    try {
                        $streamS3 = @fopen($zipFilePath, 'r');
                        if ($streamS3) {
                            Storage::disk('s3')->putStream('exports/' . $zipFileName, $streamS3);
                            if (is_resource($streamS3)) fclose($streamS3);
                        }
                    } catch (\Throwable $eS3) {
                        // Si falla S3 o no es el disco primario, intentar los siguientes
                    }
                }

                // 2. Subir al disco predeterminado del sistema (si no es S3 ni public)
                if (config('filesystems.default') !== 's3' && config('filesystems.default') !== 'public' && config('filesystems.default') !== 'local') {
                    $streamDef = @fopen($zipFilePath, 'r');
                    if ($streamDef) {
                        Storage::disk(config('filesystems.default'))->putStream('exports/' . $zipFileName, $streamDef);
                        if (is_resource($streamDef)) fclose($streamDef);
                    }
                }

                // 3. Subir al disco public local del worker actual
                $streamPub = @fopen($zipFilePath, 'r');
                if ($streamPub) {
                    Storage::disk('public')->putStream('exports/' . $zipFileName, $streamPub);
                    if (is_resource($streamPub)) fclose($streamPub);
                }
            } catch (\Throwable $e) {
                // Continuar si la sincronización remota falla
            }

            // Notificar al usuario usando SIEMPRE la ruta dinámica de previsualización/descarga (file.preview).
            // Esto evita guardar una URL pre-firmada estática en la base de datos (que expira a los 15 minutos)
            // y garantiza que en el segundo exacto en que el usuario haga clic, el controlador resuelva
            // dinámicamente el archivo en el bucket S3/Tigris o disco local y lo descargue sin error 404.
            $downloadUrl = route('file.preview', ['path' => 'exports/' . $zipFileName, 'download' => 1]);
            $user->notify(new ExportCompleted($zipFileName, $downloadUrl, 'Tus requisiciones en formato PDF están listas para descargar.'));
        }
    }
}
