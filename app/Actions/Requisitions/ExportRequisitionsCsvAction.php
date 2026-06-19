<?php

namespace App\Actions\Requisitions;

use App\Models\Requisition;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportRequisitionsCsvAction
{
    /**
     * Exporta las requisiciones seleccionadas a CSV.
     *
     * @param array $selectedIds
     * @param string $type 'summary' | 'detailed'
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function execute(array $selectedIds, string $type = 'summary'): StreamedResponse
    {
        if ($type === 'detailed') {
            $requisitions = Requisition::with(['project', 'vendor', 'creator', 'approver', 'items.product', 'items.measure'])
                ->whereIn('id', $selectedIds)
                ->get();
            $filename = 'requisiciones_detallado_' . now()->format('Ymd_His') . '.csv';
            return $this->streamDetailed($requisitions, $filename);
        }

        $requisitions = Requisition::with(['project', 'vendor', 'creator', 'approver'])
            ->whereIn('id', $selectedIds)
            ->get();
        $filename = 'requisiciones_resumen_' . now()->format('Ymd_His') . '.csv';
        return $this->streamSummary($requisitions, $filename);
    }

    private function getHeaders(string $filename): array
    {
        return [
            'Content-type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=' . $filename,
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];
    }

    private function streamSummary(Collection $requisitions, string $filename): StreamedResponse
    {
        $columns = ['Folio', 'Proyecto', 'Fecha', 'Creador', 'Proveedor', 'Total', 'Estado', 'Aprobado Por'];

        $callback = function () use ($requisitions, $columns) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF"); // BOM for Excel
            fputcsv($file, $columns);

            foreach ($requisitions as $req) {
                fputcsv($file, [
                    $req->number ?? 'REQ-' . str_pad($req->id, 5, '0', STR_PAD_LEFT),
                    $req->project->name ?? '—',
                    $req->date?->format('d/m/Y') ?? '—',
                    $req->creator->name ?? '—',
                    $req->vendor->name ?? '—',
                    $req->total,
                    ucfirst($req->status),
                    $req->approver->name ?? '—',
                ]);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $this->getHeaders($filename));
    }

    private function streamDetailed(Collection $requisitions, string $filename): StreamedResponse
    {
        $columns = [
            'Folio Requisición', 'Proyecto', 'Fecha Req', 'Proveedor Req', 'Total Req', 
            'Estado Req', 'Producto/Servicio', 'Cantidad', 'Medida', 'Precio Unitario', 'Subtotal Ítem'
        ];

        $callback = function () use ($requisitions, $columns) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, $columns);

            foreach ($requisitions as $req) {
                $folio = $req->number ?? 'REQ-' . str_pad($req->id, 5, '0', STR_PAD_LEFT);
                $project = $req->project->name ?? '—';
                $date = $req->date?->format('d/m/Y') ?? '—';
                $vendor = $req->vendor->name ?? '—';

                if ($req->items->isEmpty()) {
                    fputcsv($file, [$folio, $project, $date, $vendor, $req->total, ucfirst($req->status), '—', '—', '—', '—', '—']);
                } else {
                    foreach ($req->items as $item) {
                        $productName = $item->product ? $item->product->canonical_name : $item->description;
                        fputcsv($file, [
                            $folio, $project, $date, $vendor, $req->total, ucfirst($req->status),
                            $productName, $item->quantity, $item->measure->name ?? '—', $item->unit_price, $item->subtotal
                        ]);
                    }
                }
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $this->getHeaders($filename));
    }
}
