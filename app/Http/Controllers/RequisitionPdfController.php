<?php

namespace App\Http\Controllers;

use App\Models\Requisition;
use App\Services\RequisitionPdfService;
use Illuminate\Http\Response;

class RequisitionPdfController extends Controller
{
    public function download(int $id, RequisitionPdfService $pdfService): Response
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

        $pdf = $pdfService->generatePdf($requisition);
        $filename = $pdfService->getFilename($requisition);

        return $pdf->stream($filename);
    }
}
