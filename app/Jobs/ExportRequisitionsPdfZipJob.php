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

            // Notificar al usuario usando StorageResolver vía route('file.preview') para evitar errores 404 en Railway
            $downloadUrl = route('file.preview', ['path' => 'exports/' . $zipFileName, 'disk' => 'public', 'download' => 1]);
            $user->notify(new ExportCompleted($zipFileName, $downloadUrl, 'Tus requisiciones en formato PDF están listas para descargar.'));
        }
    }
}
