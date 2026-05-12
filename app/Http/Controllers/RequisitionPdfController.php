<?php

namespace App\Http\Controllers;

use App\Models\Requisition;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class RequisitionPdfController extends Controller
{
    public function download(int $id): Response
    {
        $requisition = Requisition::with([
            'project',
            'vendor.supplier',
            'creator',
            'approver',
            'items.product',
            'items.measure',
            'items.supplier',
        ])->findOrFail($id);

        // Logo: usar configuración o fallback al logo por defecto
        $companyLogo = Setting::get('company_logo');
        $logoData = null;

        if ($companyLogo && Storage::disk('public')->exists($companyLogo)) {
            $logoPath = Storage::disk('public')->path($companyLogo);
            $mimeType = mime_content_type($logoPath);
            $logoData = 'data:' . $mimeType . ';base64,' . base64_encode(file_get_contents($logoPath));
        } else {
            // Fallback al logo SVG por defecto
            $logoPath = public_path('images/logo_muulsinik.svg');
            $logoData = file_exists($logoPath)
                ? 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($logoPath))
                : null;
        }

        // Datos de la empresa desde configuración
        $company = [
            'name' => Setting::get('company_name', 'Constructora Muulsinik'),
            'rfc' => Setting::get('company_rfc', ''),
            'address' => Setting::get('company_address', ''),
            'phone' => Setting::get('company_phone', ''),
            'email' => Setting::get('company_email', ''),
        ];

        // Configuración de moneda
        $currency = [
            'symbol' => Setting::get('currency_symbol', '$'),
            'position' => Setting::get('currency_position', 'before'),
            'decimals' => Setting::get('decimal_places', 2),
        ];

        $pdf = Pdf::loadView('pdf.requisition', compact('requisition', 'logoData', 'company', 'currency'))
            ->setPaper('letter', 'portrait')
            ->setOption(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);

        $reqPrefix = Setting::get('req_prefix', 'REQ-');
        $filename = $reqPrefix . ($requisition->number ?? $requisition->id) . '.pdf';

        return $pdf->stream($filename);
    }
}
