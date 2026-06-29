<?php

namespace App\Actions\Reports;

use App\Services\ReportService;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportReportAction
{
    public function executeCsv(string $activeTab, Carbon $dateFrom, ?string $projectFilter = null): StreamedResponse
    {
        $reportService = app(ReportService::class);
        $timestamp = now()->format('Ymd_His');

        return match ($activeTab) {
            'suppliers' => $this->streamCsv($this->getSupplierRows($reportService, $dateFrom, $projectFilter), "reporte_proveedores_{$timestamp}.csv"),
            'vendors' => $this->streamCsv($this->getVendorRows($reportService, $dateFrom, $projectFilter), "reporte_vendedores_{$timestamp}.csv"),
            'products' => $this->streamCsv($this->getProductRows($reportService, $dateFrom, $projectFilter), "reporte_productos_{$timestamp}.csv"),
            default => $this->streamCsv($this->getOverviewRows($reportService, $dateFrom, $projectFilter), "reporte_resumen_proyectos_{$timestamp}.csv"),
        };
    }

    public function executeExcel(string $activeTab, Carbon $dateFrom, ?string $projectFilter = null): StreamedResponse
    {
        $reportService = app(ReportService::class);
        $timestamp = now()->format('Ymd_His');

        $data = match ($activeTab) {
            'suppliers' => ['title' => 'Proveedores', 'rows' => $this->getSupplierRows($reportService, $dateFrom, $projectFilter), 'filename' => "reporte_proveedores_{$timestamp}.xlsx"],
            'vendors' => ['title' => 'Vendedores', 'rows' => $this->getVendorRows($reportService, $dateFrom, $projectFilter), 'filename' => "reporte_vendedores_{$timestamp}.xlsx"],
            'products' => ['title' => 'Productos', 'rows' => $this->getProductRows($reportService, $dateFrom, $projectFilter), 'filename' => "reporte_productos_{$timestamp}.xlsx"],
            default => ['title' => 'Resumen Proyectos', 'rows' => $this->getOverviewRows($reportService, $dateFrom, $projectFilter), 'filename' => "reporte_resumen_proyectos_{$timestamp}.xlsx"],
        };

        return $this->streamExcel($data['rows'], $data['title'], $data['filename']);
    }

    private function getOverviewRows(ReportService $service, Carbon $dateFrom, ?string $projectFilter): array
    {
        $data = $service->getOverviewData($dateFrom, $projectFilter, 0);
        $projects = $data['budgetComparison'] ?? collect();

        $rows = [['Proyecto', 'Presupuesto Asignado', 'Gasto Real', 'Porcentaje Usado', 'Estado']];

        foreach ($projects as $proj) {
            $status = $proj['percent'] >= 100 ? 'Excedido' : ($proj['percent'] >= 90 ? 'Riesgo Alto' : 'En Rango');
            $rows[] = [
                $proj['name'] ?? '—',
                (float) $proj['budget'],
                (float) $proj['spent'],
                $proj['percent'] . '%',
                $status,
            ];
        }

        return $rows;
    }

    private function getSupplierRows(ReportService $service, Carbon $dateFrom, ?string $projectFilter): array
    {
        $data = $service->getSupplierData($dateFrom, $projectFilter, 0);
        $suppliers = $data['topSuppliers'] ?? collect();

        $rows = [['ID', 'Nombre Comercial', 'Categoría', 'Total Requisiciones', 'Total Partidas', 'Monto Total Comprado']];

        foreach ($suppliers as $sup) {
            $rows[] = [
                $sup->id,
                $sup->trade_name ?? '—',
                ucfirst($sup->category ?? 'General'),
                (int) $sup->total_requisitions,
                (int) $sup->total_items,
                (float) $sup->total_amount,
            ];
        }

        return $rows;
    }

    private function getVendorRows(ReportService $service, Carbon $dateFrom, ?string $projectFilter): array
    {
        $data = $service->getVendorData($dateFrom, $projectFilter, 0);
        $vendors = $data['topVendors'] ?? collect();

        $rows = [['ID', 'Vendedor / Marca', 'Empresa Proveedora', 'Total Requisiciones', 'Monto Total']];

        foreach ($vendors as $ven) {
            $rows[] = [
                $ven->id,
                $ven->vendor_name ?? '—',
                $ven->supplier_name ?? '—',
                (int) $ven->total_requisitions,
                (float) $ven->total_amount,
            ];
        }

        return $rows;
    }

    private function getProductRows(ReportService $service, Carbon $dateFrom, ?string $projectFilter): array
    {
        $data = $service->getProductData($dateFrom, $projectFilter, 0);
        $products = $data['topProducts'] ?? collect();

        $rows = [['ID / Código', 'Descripción del Producto', 'Categoría', 'Unidad de Medida', 'Veces Comprado', 'Cantidad Total', 'Precio Promedio', 'Monto Total Comprado']];

        foreach ($products as $prod) {
            $rows[] = [
                $prod->canonical_name ?? 'PROD-' . $prod->id,
                $prod->canonical_name ?? '—',
                $prod->category_name ?? 'Sin categoría',
                $prod->measure_abbr ?? 'pza',
                (int) $prod->times_purchased,
                (float) $prod->total_quantity,
                (float) $prod->avg_price,
                (float) $prod->total_amount,
            ];
        }

        return $rows;
    }

    private function streamCsv(array $rows, string $filename): StreamedResponse
    {
        $callback = function () use ($rows) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF"); // BOM para Excel
            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=' . $filename,
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    private function streamExcel(array $rows, string $sheetTitle, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($sheetTitle, 0, 30));

        if (!empty($rows)) {
            $sheet->fromArray($rows, null, 'A1');

            // Estilar fila de encabezado
            $lastColumn = $sheet->getHighestColumn();
            $headerRange = 'A1:' . $lastColumn . '1';
            $sheet->getStyle($headerRange)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E293B'], // Slate oscuro profesional
                ],
            ]);

            // Auto-ajustar columnas
            foreach (range('A', $lastColumn) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        $callback = function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename=' . $filename,
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
