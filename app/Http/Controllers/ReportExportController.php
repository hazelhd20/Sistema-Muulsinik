<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ExportReportAction;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * ReportExportController
 *
 * Maneja la descarga de archivos CSV y Excel de reportes como respuestas HTTP puras,
 * separado del ciclo de renderizado de Livewire para evitar corrupción de datos binarios.
 *
 * Los parámetros (período, tab, filtro de proyecto) viajan como query params en la URL
 * ya que son datos escalares no confidenciales. La autenticación y permisos se validan
 * en el controller antes de generar el archivo.
 */
class ReportExportController extends Controller
{
    public function csv(Request $request, ExportReportAction $action)
    {
        if (! auth()->user()?->hasPermission('reportes.ver') && ! auth()->user()?->hasPermission('*')) {
            abort(403);
        }

        $validated = $request->validate([
            'tab'           => ['required', 'string', 'in:overview,suppliers,vendors,products'],
            'period'        => ['required', 'string', 'in:week,month,quarter,year,all'],
            'project_filter' => ['nullable', 'string'],
        ]);

        $dateFrom = $this->resolveDateFrom($validated['period']);

        return $action->executeCsv(
            $validated['tab'],
            $dateFrom,
            $validated['project_filter'] ?: null
        );
    }

    public function excel(Request $request, ExportReportAction $action)
    {
        if (! auth()->user()?->hasPermission('reportes.ver') && ! auth()->user()?->hasPermission('*')) {
            abort(403);
        }

        $validated = $request->validate([
            'tab'           => ['required', 'string', 'in:overview,suppliers,vendors,products'],
            'period'        => ['required', 'string', 'in:week,month,quarter,year,all'],
            'project_filter' => ['nullable', 'string'],
        ]);

        $dateFrom = $this->resolveDateFrom($validated['period']);

        return $action->executeExcel(
            $validated['tab'],
            $dateFrom,
            $validated['project_filter'] ?: null
        );
    }

    private function resolveDateFrom(string $period): Carbon
    {
        return match ($period) {
            'week'    => now()->subWeek(),
            'month'   => now()->subMonth(),
            'quarter' => now()->subQuarter(),
            'year'    => now()->subYear(),
            'all'     => now()->subYears(10),
            default   => now()->subMonth(),
        };
    }
}
