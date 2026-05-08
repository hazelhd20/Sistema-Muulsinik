<?php

namespace App\Http\Controllers;

use App\Models\Requisition;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

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

        $logoPath = public_path('images/logo_muulsinik.svg');
        $logoData = file_exists($logoPath)
            ? 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $pdf = Pdf::loadView('pdf.requisition', compact('requisition', 'logoData'))
            ->setPaper('letter', 'portrait')
            ->setOption(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);

        $filename = 'REQ-' . ($requisition->number ?? $requisition->id) . '.pdf';

        return $pdf->stream($filename);
    }
}
